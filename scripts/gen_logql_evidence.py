import urllib.request, urllib.parse, time, json

now_ns = int(time.time() * 1e9)
start_ns = now_ns - int(3600 * 1e9)

def run_query(q):
    params = urllib.parse.urlencode({"query": q, "start": start_ns, "end": now_ns, "limit": 20})
    url = f"http://172.22.0.11:3100/loki/api/v1/query_range?{params}"
    try:
        req = urllib.request.urlopen(url)
        data = json.loads(req.read().decode())
        return data.get("data", {}).get("result", [])
    except Exception as e:
        print(f"Error on query {q}: {e}")
        return []

out = []
out.append("================================================================================")
out.append("BUOC 5: MINH CHUNG TRUY VAN LOGQL QUA LOKI & PROMTAIL")
out.append("Thoi gian thuc hien: " + time.strftime("%Y-%m-%d %H:%M:%S"))
out.append("He thong: Gym & Fitness Management - Docker Compose")
out.append("================================================================================\n")

# Query 1: Nginx 5xx error
q1 = '{job="nginx"} |~ " (500|502|503|504) "'
res1 = run_query(q1)
out.append("--- TRUY VAN 1: TIM LOI 5XX TREN NGINX REVERSE PROXY ---")
out.append("LogQL: " + q1)
out.append(f"So ban ghi tim thay: {sum(len(r.get('values', [])) for r in res1)}")
for r in res1:
    for v in r.get("values", [])[:5]:
        out.append(f"  [{time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(int(v[0])/1e9))}] {v[1]}")
out.append("")

# Query 2: Webapp general error
q2 = '{job="webapp"} |~ "(?i)(error|exception|failed)"'
res2 = run_query(q2)
out.append("--- TRUY VAN 2: TIM LOI HOAC NGOAI LE TREN WEB APPLICATION ---")
out.append("LogQL: " + q2)
out.append(f"So ban ghi tim thay: {sum(len(r.get('values', [])) for r in res2)}")
for r in res2:
    for v in r.get("values", [])[:5]:
        out.append(f"  [{time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(int(v[0])/1e9))}] {v[1]}")
out.append("")

# Query 3: Login failed attempts
q3 = '{job="webapp"} |= "LOGIN_FAILED"'
res3 = run_query(q3)
out.append("--- TRUY VAN 3: TIM CAC HANH VI DANG NHAP THAT BAI (BRUTE FORCE FILTER) ---")
out.append("LogQL: " + q3)
out.append(f"So ban ghi tim thay: {sum(len(r.get('values', [])) for r in res3)}")
for r in res3:
    for v in r.get("values", [])[:10]:
        out.append(f"  [{time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(int(v[0])/1e9))}] {v[1]}")
out.append("")

# Query 4: Metric rate of login failed
q4 = 'sum(count_over_time({job="webapp"} |= "LOGIN_FAILED" [5m]))'
url4 = f"http://172.22.0.11:3100/loki/api/v1/query?query={urllib.parse.quote(q4)}"
try:
    req4 = urllib.request.urlopen(url4)
    data4 = json.loads(req4.read().decode())
    res4 = data4.get("data", {}).get("result", [])
    out.append("--- TRUY VAN 4: DO LUONG TAN SUAT DANG NHAP THAT BAI TRONG 5 PHUT (METRIC QUERY) ---")
    out.append("LogQL: " + q4)
    if res4:
        out.append(f"Gia tri dem duoc (Metric value): {res4[0].get('value', [None, 0])[1]} lan")
except Exception as e:
    out.append(f"Error query 4: {e}")
out.append("\n================================================================================")

with open("/home/huyenxinh/gym/evidence/buoc5-logql.txt", "w", encoding="utf-8") as f:
    f.write("\n".join(out))
print("Wrote evidence/buoc5-logql.txt successfully!")