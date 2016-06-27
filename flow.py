#!/usr/local/bin/python
#-*- coding: utf-8 -*-

import scapy.all


def handle_packet(packet):
    #print packet.__dict__
    print packet.payload.len
    return packet.summary()

def main():
    scapy.all.sniff(filter="ip", count=1, iface="em2", prn=handle_packet, store=0)

if __name__ == "__main__":
    main()
