import urllib.request, urllib.parse, ssl, re, http.cookiejar, time, random

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj), urllib.request.HTTPSHandler(context=ctx))

def do_requests():
    try:
        resp = opener.open("https://127.0.0.1/login.php", timeout=5).read().decode()
        m = re.search(r'name="csrf_token" value="([a-f0-9]+)"', resp)
        token = m.group(1) if m else ""
        for _ in range(random.randint(4, 10)):
            data = urllib.parse.urlencode({"csrf_token": token, "username": "admin", "password": "wrongpassword"}).encode()
            req = urllib.request.Request("https://127.0.0.1/login.php", data=data)
            opener.open(req, timeout=5)
        for p in ["/", "/index.php", "/login.php", "/notfound.php"]:
            try:
                opener.open(f"https://127.0.0.1{p}", timeout=5)
            except:
                pass
    except Exception as e:
        pass

while True:
    do_requests()
    time.sleep(10)
