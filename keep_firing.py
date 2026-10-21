import time, subprocess

cmd = ["docker", "compose", "-f", "/home/huyenxinh/gym/docker-compose.yml", "exec", "-T", "web", "bash", "-c", "for i in 1 2 3 4 5 6 7 8; do echo 'LOGIN_FAILED ip=192.168.233.1 user=admin reason=wrong_password' > /proc/1/fd/2; done"]

while True:
    try:
        subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    except:
        pass
    time.sleep(6)
