import http from "node:http";
import { pathToFileURL } from "node:url";

// HTTP infrastructure only: every PHP operation comes from a browser request.
// Reserve independent capacity for reads while manual mutations are running.
export function createApiServer({
  readPorts = [9081, 9082],
  writePorts = [9083, 9084],
} = {}) {
  const reads = readPorts.map((port) => ({ port, active: 0 }));
  const writes = writePorts.map((port) => ({ port, active: 0 }));
  const server = http.createServer((request, response) => {
    const pool = ["GET", "HEAD", "OPTIONS"].includes(request.method)
      ? reads
      : writes;
    const target = pool.reduce((best, entry) =>
      entry.active < best.active ? entry : best,
    );
    target.active++;
    let released = false;
    const release = () => {
      if (released) return;
      released = true;
      target.active--;
    };
    // Never retry: repeating a POST could import data or send an email twice.
    const upstream = http.request(
      {
        hostname: "127.0.0.1",
        port: target.port,
        method: request.method,
        path: request.url,
        headers: { ...request.headers, connection: "close" },
      },
      (result) => {
        response.writeHead(result.statusCode, result.headers);
        result.pipe(response);
        result.once("end", release);
        result.once("error", () => {
          release();
          response.destroy();
        });
        result.once("close", release);
      },
    );
    upstream.once("error", () => {
      release();
      if (response.headersSent) {
        response.destroy();
        return;
      }
      response.writeHead(502, { "Content-Type": "application/json" });
      response.end(
        JSON.stringify({
          message:
            "O processo PHP está indisponível. Verifique o servidor local antes de repetir a operação.",
        }),
      );
    });
    request.once("aborted", () => upstream.destroy());
    response.once("close", () => {
      if (!response.writableFinished) upstream.destroy();
    });
    request.pipe(upstream);
  });
  // Complete manual PNCP queries can take longer than Node's default timeout.
  server.requestTimeout = 0;
  return server;
}

if (
  process.argv[1] &&
  import.meta.url === pathToFileURL(process.argv[1]).href
) {
  const server = createApiServer();
  server.on("error", (error) => {
    console.error(error.message);
    process.exitCode = 1;
  });
  server.listen(8000, "127.0.0.1", () =>
    console.log("API HTTP concorrente: http://127.0.0.1:8000"),
  );
  for (const signal of ["SIGINT", "SIGTERM"]) {
    process.on(signal, () => server.close(() => process.exit(0)));
  }
}
