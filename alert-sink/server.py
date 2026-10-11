from http.server import HTTPServer, BaseHTTPRequestHandler
import json, sys, datetime

class AlertHandler(BaseHTTPRequestHandler):
    def do_POST(self):
        length = int(self.headers.get('content-length', 0))
        data = self.rfile.read(length).decode('utf-8')
        timestamp = datetime.datetime.now().isoformat()
        log_line = f"[{timestamp}] ALERT RECEIVED: {data}\n"
        sys.stdout.write(log_line)
        sys.stdout.flush()
        with open("/var/log/alerts.log", "a", encoding="utf-8") as f:
            f.write(log_line)
        self.send_response(200)
        self.end_headers()
        self.wfile.write(b"OK")

server = HTTPServer(('0.0.0.0', 8080), AlertHandler)
print("Alert Sink listening on port 8080...")
server.serve_forever()
