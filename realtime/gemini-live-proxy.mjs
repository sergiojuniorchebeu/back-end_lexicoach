import http from 'node:http';
import { existsSync, readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { WebSocketServer, WebSocket } from 'ws';

loadEnvFile();

const port = Number.parseInt(process.env.GEMINI_LIVE_PROXY_PORT ?? '8787', 10);
const host = process.env.GEMINI_LIVE_PROXY_HOST ?? '127.0.0.1';
const laravelApiUrl = (process.env.LARAVEL_API_URL ?? 'http://127.0.0.1:8000/api').replace(/\/$/, '');
const geminiApiKey = process.env.GEMINI_API_KEY;
const geminiModel = process.env.GEMINI_LIVE_MODEL ?? 'gemini-3.8-live';
const responseModality = process.env.GEMINI_LIVE_RESPONSE_MODALITY ?? 'TEXT';
const systemInstruction =
  process.env.GEMINI_LIVE_SYSTEM_INSTRUCTION ??
  'You are LexiCoach, a patient language learning coach. Keep answers short, kind, and useful for a beginner learner.';

if (!geminiApiKey) {
  console.error('GEMINI_API_KEY is required to start the Gemini Live proxy.');
  process.exit(1);
}

const server = http.createServer((request, response) => {
  if (request.url === '/health') {
    response.writeHead(200, { 'content-type': 'application/json' });
    response.end(JSON.stringify({ ok: true }));
    return;
  }

  response.writeHead(404, { 'content-type': 'application/json' });
  response.end(JSON.stringify({ ok: false }));
});

const wss = new WebSocketServer({ server, path: '/ai-conversations/live' });

wss.on('connection', async (client, request) => {
  let gemini = null;
  let session = null;
  let expiryTimer = null;

  try {
    const url = new URL(request.url ?? '', `http://${request.headers.host}`);
    const token = url.searchParams.get('token');
    const sessionId = url.searchParams.get('session_id');

    if (!token || !sessionId) {
      sendJson(client, {
        type: 'error',
        message: 'Missing token or session_id.',
      });
      client.close(1008, 'Unauthorized');
      return;
    }

    const authorization = await authorizeSession({ token, sessionId });
    session = authorization.session;

    gemini = new WebSocket(
      `wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1beta.GenerativeService.BidiGenerateContent?key=${encodeURIComponent(geminiApiKey)}`,
    );

    gemini.on('open', () => {
      const setup = {
        setup: {
          model: `models/${geminiModel}`,
          generationConfig: {
            responseModalities: [responseModality],
          },
          realtimeInputConfig: {
            automaticActivityDetection: {
              disabled: true,
            },
          },
          inputAudioTranscription: {},
          outputAudioTranscription: {},
          systemInstruction: {
            parts: [{ text: systemInstruction }],
          },
        },
      };

      gemini.send(JSON.stringify(setup));
      sendJson(client, {
        type: 'ready',
        session,
        model: geminiModel,
        response_modality: responseModality,
      });
    });

    gemini.on('message', (message) => {
      const payload = parseJson(message);
      const normalized = normalizeGeminiMessage(payload);
      const assistantText =
        normalized.text ||
        normalized.output_transcription_final ||
        (normalized.turn_complete ? normalized.output_transcription_interim : null);
      const learnerText = normalized.input_transcription_final;

      if (learnerText) {
        void persistMessage({
          token,
          sessionId,
          role: 'learner',
          content: learnerText,
          metadata: { source: 'gemini_live_input_transcription' },
        });
      }

      if (assistantText) {
        void persistMessage({
          token,
          sessionId,
          role: 'assistant',
          content: assistantText,
          metadata: { source: 'gemini_live' },
        });
      }

      sendJson(client, {
        type: 'gemini',
        payload,
        ...normalized,
      });
    });

    gemini.on('error', (error) => {
      sendJson(client, {
        type: 'error',
        message: error.message,
      });
    });

    gemini.on('close', (code, reason) => {
      sendJson(client, {
        type: 'closed',
        source: 'gemini',
        code,
        reason: reason.toString(),
      });
      client.close();
    });

    client.on('message', (message) => {
      if (!gemini || gemini.readyState !== WebSocket.OPEN) {
        sendJson(client, {
          type: 'error',
          message: 'Gemini socket is not ready yet.',
        });
        return;
      }

      const payload = parseJson(message);
      const geminiMessage = toGeminiMessage(payload);

      if (!geminiMessage) {
        sendJson(client, {
          type: 'error',
          message: 'Unsupported realtime message.',
        });
        return;
      }

      gemini.send(JSON.stringify(geminiMessage));

      if (payload.type === 'text' && typeof payload.text === 'string') {
        void persistMessage({
          token,
          sessionId,
          role: 'learner',
          content: payload.text,
          metadata: { source: 'mobile_text' },
        });
      }
    });

    client.on('close', () => {
      clearTimeout(expiryTimer);
      if (gemini && gemini.readyState === WebSocket.OPEN) {
        gemini.close();
      }
    });

    expiryTimer = setTimeout(() => {
      sendJson(client, {
        type: 'expired',
        message: 'Session time limit reached.',
      });
      client.close(1000, 'Session expired');
      if (gemini && gemini.readyState === WebSocket.OPEN) {
        gemini.close();
      }
    }, Math.max(1, session.remaining_seconds) * 1000);
  } catch (error) {
    sendJson(client, {
      type: 'error',
      message: error.message,
    });
    client.close(1011, 'Proxy error');
    if (gemini && gemini.readyState === WebSocket.OPEN) {
      gemini.close();
    }
    clearTimeout(expiryTimer);
  }
});

server.listen(port, host, () => {
  console.log(`Gemini Live proxy listening on ws://${host}:${port}/ai-conversations/live`);
});

async function authorizeSession({ token, sessionId }) {
  const response = await fetch(
    `${laravelApiUrl}/ai-conversations/${encodeURIComponent(sessionId)}/realtime-authorize`,
    {
      headers: {
        accept: 'application/json',
        authorization: `Bearer ${token}`,
      },
    },
  );

  const payload = await response.json();

  if (!response.ok) {
    throw new Error(payload.message ?? 'Realtime authorization failed.');
  }

  return payload.data;
}

async function persistMessage({ token, sessionId, role, content, metadata = {} }) {
  if (!content || content.trim() === '') {
    return;
  }

  try {
    const response = await fetch(
      `${laravelApiUrl}/ai-conversations/${encodeURIComponent(sessionId)}/messages`,
      {
        method: 'POST',
        headers: {
          accept: 'application/json',
          'content-type': 'application/json',
          authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ role, content, metadata }),
      },
    );

    if (!response.ok) {
      console.warn(`Unable to persist ${role} message: ${response.status}`);
    }
  } catch (error) {
    console.warn(`Unable to persist ${role} message: ${error.message}`);
  }
}

function toGeminiMessage(payload) {
  if (!payload || typeof payload !== 'object') {
    return null;
  }

  if (payload.type === 'text' && typeof payload.text === 'string') {
    return {
      realtimeInput: {
        text: payload.text,
      },
    };
  }

  if (payload.type === 'audio' && typeof payload.data === 'string') {
    return {
      realtimeInput: {
        audio: {
          data: payload.data,
          mimeType: payload.mimeType ?? 'audio/pcm;rate=16000',
        },
      },
    };
  }

  if (payload.type === 'audio_stream_end') {
    return {
      realtimeInput: {
        audioStreamEnd: true,
      },
    };
  }

  if (payload.type === 'activity_start') {
    return {
      realtimeInput: {
        activityStart: {},
      },
    };
  }

  if (payload.type === 'activity_end') {
    return {
      realtimeInput: {
        activityEnd: {},
      },
    };
  }

  return null;
}

function normalizeGeminiMessage(payload) {
  const content = payload?.serverContent;
  const parts = content?.modelTurn?.parts ?? [];
  const textParts = parts
    .map((part) => part.text)
    .filter((text) => typeof text === 'string' && text.length > 0);
  const audioParts = parts
    .map((part) => part.inlineData)
    .filter((inlineData) => inlineData?.data);

  return {
    text: textParts.join(''),
    input_transcription_final: content?.inputTranscription?.text ?? null,
    input_transcription_interim: content?.interimInputTranscription?.text ?? null,
    output_transcription_final: content?.outputTranscription?.text ?? null,
    output_transcription_interim: content?.interimOutputTranscription?.text ?? null,
    audio: audioParts.length > 0 ? audioParts : null,
    turn_complete: content?.turnComplete === true,
    generation_complete: content?.generationComplete === true,
    interrupted: content?.interrupted === true,
  };
}

function parseJson(message) {
  try {
    return JSON.parse(message.toString());
  } catch {
    return null;
  }
}

function sendJson(socket, payload) {
  if (socket.readyState === WebSocket.OPEN) {
    socket.send(JSON.stringify(payload));
  }
}

function loadEnvFile() {
  const currentDir = dirname(fileURLToPath(import.meta.url));
  const envPath = resolve(currentDir, '../.env');

  if (!existsSync(envPath)) {
    return;
  }

  const lines = readFileSync(envPath, 'utf8').split(/\r?\n/);

  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) {
      continue;
    }

    const separatorIndex = trimmed.indexOf('=');
    if (separatorIndex === -1) {
      continue;
    }

    const key = trimmed.slice(0, separatorIndex).trim();
    let value = trimmed.slice(separatorIndex + 1).trim();

    if (!key || process.env[key] !== undefined) {
      continue;
    }

    if (
      (value.startsWith('"') && value.endsWith('"')) ||
      (value.startsWith("'") && value.endsWith("'"))
    ) {
      value = value.slice(1, -1);
    }

    process.env[key] = value;
  }
}
