import sys,json,httplib,urllib,base64,socket

server,port = sys.argv[1].split(":")
vhost = sys.argv[2]
username = sys.argv[3]
password = sys.argv[4]

conn = httplib.HTTPConnection(server,port)

path = "/api/aliveness-test/%s" % urllib.quote(vhost, safe="")
method = "GET"

credentials = base64.b64encode("%s:%s" % (username,password))

try:
    conn.request(method, path, "",
                    {"Content-Type" : "application/json",
                     "Authorization" : "Basic " + credentials})

except socket.error:
    print "CRITICAL:Could not connect to %s:%s" % (server, port)
    exit(0)

response = conn.getresponse()

if response.status > 299:
    print "CRETICAL:Broker not alive: %s" % response.read()
    exit(0)

print "OK: Broker alive: %s" % response.read()
exit(1)