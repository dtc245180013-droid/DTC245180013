import urllib.request
import urllib.parse
import time
import json

now_ns = int(time.time() * 1e9)
start_ns = now_ns - int(3600 * 1e9)

def qr(query_str):
    params = urllib.parse.urlencode({
        "query": query_str,
        "start": start_ns,
        "end": now_ns,
        "limit": 50
    })
    url = f"http://172.22.0.11:3100/loki/api/v1/query_range?{params}"
    try:
        with urllib.request.urlopen(url) as response:
            res = json.loads(response.read().decode())
            print(f"=== QUERY_RANGE: {query_str} ===")
            print("Status:", res.get("status"))
            results = res.get("data", {}).get("result", [])
            print(f"Results count: {len(results)}")
            for item in results:
                metric = item.get("metric", {})
                values = item.get("values", [])
                print(f"  Stream: {metric} (total logs: {len(values)})")
                for v in values[:3]:
                    print(f"    [{v[0]}] {v[1][:140]}")
    except Exception as e:
        print("Error:", e)

print("--- TESTING LOGQL QUERY_RANGE ---")
qr('{job="webapp"} |= "LOGIN_FAILED"')
qr('{job="webapp"} |~ "(?i)(error|exception|failed)"')
qr('{job="nginx"}')
