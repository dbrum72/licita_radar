import { test } from "node:test";
import assert from "node:assert/strict";
import http from "node:http";
import { createApiServer } from "./http-server.mjs";

async function listen(server) {
  await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
  return server.address().port;
}
async function close(server) {
  server.closeAllConnections();
  await new Promise((resolve) => server.close(resolve));
}

test("leituras continuam disponíveis durante ação manual longa, preservando JWT e corpo", async () => {
  let finish, started;
  const actionStarted = new Promise((resolve) => {
    started = resolve;
  });
  const write = http.createServer(async (req, res) => {
    let body = "";
    for await (const chunk of req) body += chunk;
    assert.equal(req.headers.authorization, "Bearer test-token");
    assert.equal(body, '{"profile_ids":[3]}');
    started();
    await new Promise((resolve) => {
      finish = resolve;
    });
    res.end("ação concluída");
  });
  const read = http.createServer((req, res) => {
    assert.equal(req.url, "/api/me?check=1");
    assert.equal(req.headers.authorization, "Bearer test-token");
    res.setHeader("Content-Type", "application/json");
    res.end('{"name":"Ana"}');
  });
  const proxy = createApiServer({
    readPorts: [await listen(read)],
    writePorts: [await listen(write)],
  });
  const port = await listen(proxy);
  try {
    const action = fetch(`http://127.0.0.1:${port}/api/sync`, {
      method: "POST",
      headers: { Authorization: "Bearer test-token" },
      body: '{"profile_ids":[3]}',
    });
    await actionStarted;
    const result = await fetch(`http://127.0.0.1:${port}/api/me?check=1`, {
      headers: { Authorization: "Bearer test-token" },
      signal: AbortSignal.timeout(2000),
    });
    assert.equal(result.status, 200);
    assert.deepEqual(await result.json(), { name: "Ana" });
    finish();
    assert.equal(await (await action).text(), "ação concluída");
  } finally {
    finish?.();
    await Promise.all([close(proxy), close(read), close(write)]);
  }
});

test("falha do PHP retorna JSON sem repetir POST em outro processo", async () => {
  let calls = 0;
  const broken = http.createServer((req) => {
    calls++;
    req.socket.destroy();
  });
  const fallback = http.createServer((_req, res) => {
    calls++;
    res.end("não deveria executar");
  });
  const first = await listen(broken);
  const second = await listen(fallback);
  const proxy = createApiServer({
    readPorts: [first],
    writePorts: [first, second],
  });
  const port = await listen(proxy);
  try {
    const result = await fetch(`http://127.0.0.1:${port}/api/digest`, {
      method: "POST",
      body: "{}",
    });
    assert.equal(result.status, 502);
    assert.match((await result.json()).message, /PHP/);
    assert.equal(calls, 1);
  } finally {
    await Promise.all([close(proxy), close(broken), close(fallback)]);
  }
});
