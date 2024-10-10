#!/bin/bash

#BASIC RULES 

Echo  1  >  / proc / sys / net / ipv4 / ip_forward

iptables  -F

iptables  -t  nat  -F

iptables  -A  INPUT  -j  DROP

iptables  -A  FORWARD  -j  DROP

iptables  -A  OUTPUT  -j  ACCEPT

#FORWARD'S RULES WE WISH 


#ETH0 = Connects to the Router
#ETH1 = Connects to the DMZ
#ETH2 = Connects to the Local Serverr?
#We are not using ethernet, so these lines are voided

# LET'S ALL TRAFFIC GO FROM ETH0 TO ETH1 

# sudo iptables  -A  FORWARD  -i  eth0  -o  eth1  -s  state  --state  NEW, ESTABLISHED, RELATED  -j  ACCEPT

# LET'S THE RESPONSE ONLY GO TO PETITIONS FROM ETH1 TO ETH0 

# sudo iptables  -A  FORWARD  -i  eth1  -o  eth0  -s  state  --state  ESTABLISHED, RELATED  -j  ACCEPT

# LET'S ALL TRAFFIC GO FROM ETH2 TO ETH1 

# sudo iptables  -A  FORWARD  -i  eth2  -o  eth1  -s  state  --state  NEW, ESTABLISHED, RELATED  -j  ACCEPT

# LETS ONLY PASSES RESPONSES TO PETITIONS FROM ETH1 TO ETH2 

# sudo iptables  -A  FORWARD  -i  eth1  , or  eth2  -s  state  --state  ESTABLISHED, RELATED  -j  ACCEPT



#PRIORITIZE REDIRECTIONS FROM THE OUTSIDE TO THE DMZ

#IPS FICTICS THAT ARE WITHIN THE ETH1 RANGE

#TCP Port 1414, Connect to the Message Router (may or may not work)

sudo iptables  -t  nat  -A  PREROUTING  -i  eth0  -p  tcp  --dport  53  -j  DNAT  --to  192.168.2.4: 1414


#alternative prerouting lines

#sudo iptables -t nat -A PREROUTING -p $LAN_IFACE -d ???.???.???.? (Data source IP address) \ -j DNAT --to-destination 172.29.4.30


#sudo iptables -t nat -A PREROUTING -p tcp --dport 1414 -j ACCEPT
#sudo iptables -t nat -A PREROUTING -p tcp --dport ?? (Data source port) -j ACCEPT
