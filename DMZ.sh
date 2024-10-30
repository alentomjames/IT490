#!/bin/bash

#BASIC RULES 

Echo  1  >  / proc / sys / net / ipv4 / ip_forward

iptables  -F

iptables  -t  nat  -F

iptables  -A  INPUT  -j  DROP

iptables  -A  FORWARD  -j  DROP

iptables  -A  OUTPUT  -j  ACCEPT


# Allow Incoming SSH from Message Router

sudo iptables -A INPUT -p tcp -s 172.29.4.30/24 --dport 22 -m conntrack --ctstate NEW,ESTABLISHED -j ACCEPT
sudo iptables -A OUTPUT -p tcp --sport 22 -m conntrack --ctstate ESTABLISHED -j ACCEPT

# Allow Outgoing SSH

sudo iptables -A OUTPUT -p tcp --dport 22 -m conntrack --ctstate NEW,ESTABLISHED -j ACCEPT
sudo iptables -A INPUT -p tcp --sport 22 -m conntrack --ctstate ESTABLISHED -j ACCEPT

# Allow Incoming HTTPS (Port 443 is what TMDB API run on)

sudo iptables -A INPUT -p tcp --dport 443 -m conntrack --ctstate NEW,ESTABLISHED -j ACCEPT
sudo iptables -A OUTPUT -p tcp --sport 443 -m conntrack --ctstate ESTABLISHED -j ACCEPT


