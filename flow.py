#!/usr/local/bin/python
#-*- coding: utf-8 -*-

import argparse
import os
import sys
import datetime
import scapy.all
import pickle
import netaddr

args = None
flow = {}

UPLOAD = 0
DOWNLOAD = 1

def add_flow(local, remote, direction, now, proto, length):
    global flow
    if local not in flow:
        flow[local] = ({}, {})
    if now not in flow[local][direction]:
        flow[local][direction][now] = {}
    if remote not in flow[local][direction][now]:
        flow[local][direction][now][remote] = {}
    if proto not in flow[local][direction][now][remote]:
        flow[local][direction][now][remote][proto] = 0
    flow[local][direction][now][remote][proto] += length

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

    #try:
    if True:
        print "start sniffing"
        scapy.all.sniff(filter="ip", count=100, iface=args['listen'], prn=handle_packet, store=0)
    #except Exception, e:
    #    print e
    #    exit(1)
    #except e:
    #    print "Unknown error"
    #    exit(1)
    print "\rdumping data..."
    if args['debug']: print flow
    dumpfile = open(args['dump'], 'wb')
    pickle.dump(flow, dumpfile)
    dumpfile.close()

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description = "Flow Capture", prog = sys.argv[0])
    parser.add_argument('listen', help = "Listen interface")
    parser.add_argument('network', help = "Counting Network")
    parser.add_argument('-f', dest = 'dump', help = "Dump File", default='data.dmp')
    parser.add_argument('-d', dest = 'debug', action="store_true", help = "Debug mode")
    args = vars(parser.parse_args(sys.argv[1:]))
    if args['debug']: print args

    #try:
    main(args)
    #except:
        #print "Unknown error in main"
        #exit(1)
