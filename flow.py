#!/usr/local/bin/python
#-*- coding: utf-8 -*-

import argparse
import os
import sys
import datetime
import thread
import scapy.all
import pickle
import netaddr
import json
import flask

args = None
flow = {}

UPLOAD = 0
DOWNLOAD = 1
app = flask.Flask(__name__)

def add_flow(local, remote, direction, now, proto, length):
    global flow
    if local not in flow:
        flow[local] = {}
    if now not in flow[local]:
        flow[local][now] = ({}, {})
    if remote not in flow[local][now][direction]:
        flow[local][now][direction][remote] = {}
    if proto not in flow[local][now][direction][remote]:
        flow[local][now][direction][remote][proto] = 0
    flow[local][now][direction][remote][proto] += length

def handle_packet(packet):
    packet = packet.payload # switch to layer 3
    now = datetime.datetime.now().strftime('%Y%m%d%H%M')
    if netaddr.IPAddress(packet.src) in netaddr.IPNetwork(args['network']):
        proto = 'Other'
        if packet.haslayer(scapy.all.TCP): proto = packet.payload.dport
        if packet.haslayer(scapy.all.UDP): proto = packet.payload.dport
        add_flow(
            packet.src,
            packet.dst,
            UPLOAD,
            now,
            proto,
            packet.len
        )
    if netaddr.IPAddress(packet.dst) in netaddr.IPNetwork(args['network']):
        proto = 'Other'
        if packet.haslayer(scapy.all.TCP): proto = packet.payload.sport
        if packet.haslayer(scapy.all.UDP): proto = packet.payload.sport
        add_flow(
            packet.dst,
            packet.src,
            DOWNLOAD,
            now,
            proto,
            packet.len
        )
    if args['debug'] == True: return packet.summary()

def main(args):
    global flow
    if os.path.exists(args['dump']):
        dumpfile = open(args['dump'], 'rb')
        try:
            flow = pickle.load(dumpfile)
        except:
            print "Dump File not recognize"
            flow = {}
        dumpfile.close()
    if args['debug']: print flow

    try:
        print "start sniffing"
        scapy.all.sniff(filter="ip", iface=args['listen'], prn=handle_packet, store=10)
    except Exception, e:
        print e
        exit(1)
    except e:
        print "Unknown error"
        exit(1)

@app.route('/flows/')
def flow_list():
    result = []
    for l in flow:
        result.append(l)
    return json.dumps(result)

@app.route('/flows/<ip>')
@app.route('/flows/<ip>/')
def flow_ip(ip):
    result = {}
    #flow[local][now][direction][remote][proto] -> length
    if ip not in flow:
        result = None
    else:
        for t in flow[ip]:
            result[t] = [0, 0]
            for r in flow[ip][t][DOWNLOAD]:
                for p in flow[ip][t][DOWNLOAD][r]:
                    result[t][DOWNLOAD] += flow[ip][t][DOWNLOAD][r][p]
            for r in flow[ip][t][UPLOAD]:
                for p in flow[ip][t][UPLOAD][r]:
                    result[t][UPLOAD] += flow[ip][t][UPLOAD][r][p]
    return json.dumps(result)

@app.route('/flows/<ip>/remote/<remote>')
@app.route('/flows/<ip>/remote/<remote>/')
def flow_ip_remote(ip, remote):
    result = {}
    #flow[local][now][direction][remote][proto] -> length
    if ip not in flow:
        result = None
    else:
        for t in flow[ip]:
            result[t] = [0, 0]
            if remote in flow[ip][t][DOWNLOAD]:
                for p in flow[ip][t][DOWNLOAD][remote]:
                    result[t][DOWNLOAD] += flow[ip][t][DOWNLOAD][remote][p]
            if remote in flow[ip][t][UPLOAD]:
                for p in flow[ip][t][UPLOAD][remote]:
                    result[t][UPLOAD] += flow[ip][t][UPLOAD][remote][p]
    return json.dumps(result)

@app.route('/flows/<ip>/time/<time>')
@app.route('/flows/<ip>/time/<time>/')
def flow_ip_time(ip, time):
    result = {}
    #flow[local][now][direction][remote][proto] -> length
    if ip not in flow:
        result = None
    elif time not in flow[ip]:
        result = None
    else:
        for r in flow[ip][time][DOWNLOAD]:
            if r not in result: result[r] = [0, 0]
            for p in flow[ip][time][DOWNLOAD][r]:
                result[r][DOWNLOAD] += flow[ip][time][DOWNLOAD][r][p]
        for r in flow[ip][time][UPLOAD]:
            if r not in result: result[r] = [0, 0]
            for p in flow[ip][time][UPLOAD][r]:
                result[r][UPLOAD] += flow[ip][time][UPLOAD][r][p]
    return json.dumps(result)

@app.route('/flows/<ip>/<time>/<remote>')
@app.route('/flows/<ip>/<time>/<remote>/')
def flow_ip_time_remote(ip, time, remote):
    result = {}
    #flow[local][now][direction][remote][proto] -> length
    if ip not in flow:
        result = None
    elif time not in flow[ip]:
        result = None
    elif remote not in flow[ip][time][DOWNLOAD] and remote not in flow[ip][time][UPLOAD]:
        result = None
    else:
        if remote in flow[ip][time][DOWNLOAD]:
            for p in flow[ip][time][DOWNLOAD][remote]:
                if p not in result: result[p] = [0, 0]
                result[p][DOWNLOAD] += flow[ip][time][DOWNLOAD][remote][p]
        if remote in flow[ip][time][UPLOAD]:
            for p in flow[ip][time][UPLOAD][remote]:
                if p not in result: result[p] = [0, 0]
                result[p][UPLOAD] += flow[ip][time][UPLOAD][remote][p]
    return json.dumps(result)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description = "Flow Capture", prog = sys.argv[0])
    parser.add_argument('listen', help = "Listen interface")
    parser.add_argument('network', help = "Counting Network")
    parser.add_argument('-f', dest = 'dump', help = "Dump File", default='data.dmp')
    parser.add_argument('-p', dest = 'port', type = int, help = "Server Port", default=3000)
    parser.add_argument('-d', dest = 'debug', action="store_true", help = "Debug mode")
    args = vars(parser.parse_args(sys.argv[1:]))
    if args['debug']: print args

    #try:
    thread.start_new_thread(main, (args, ))
    #main(args)
    app.run(host = '0.0.0.0', port = args['port'])
    #except:
        #print "Unknown error in main"
        #exit(1)

    print "\rdumping data..."
    if args['debug']: print flow
    dumpfile = open(args['dump'], 'wb')
    pickle.dump(flow, dumpfile)
    dumpfile.close()
