#! / Bin / bash

#BASIC RULES 

Echo  1  >  / proc / sys / net / ipv4 / ip_forward

iptables  -F

iptables  -t  nat  -F

iptables  -A  INPUT  -j  DROP

iptables  -A  FORWARD  -j  DROP

iptables  -A  OUTPUT  -j  ACCEPT

#FORWARD'S RULES WE WISH 

# LET'S ALL TRAFFIC GO FROM ETH0 TO ETH1 

iptables  -A  FORWARD  -i  eth0  -o  eth1  -s  state  --state  NEW, ESTABLISHED, RELATED  -j  ACCEPT

# LET'S THE RESPONSE ONLY GO TO PETITIONS FROM ETH1 TO ETH0 

iptables  -A  FORWARD  -i  eth1  -o  eth0  -s  state  --state  ESTABLISHED, RELATED  -j  ACCEPT

# LET'S ALL TRAFFIC GO FROM ETH2 TO ETH1 

iptables  -A  FORWARD  -i  eth2  -o  eth1  -s  state  --state  NEW, ESTABLISHED, RELATED  -j  ACCEPT

# LETS ONLY PASSES RESPONSES TO PETITIONS FROM ETH1 TO ETH2 

iptables  -A  FORWARD  -i  eth1  , or  eth2  -s  state  --state  ESTABLISHED, RELATED  -j  ACCEPT

#PRIORITIZE REDIRECTIONS FROM THE OUTSIDE TO THE DMZ

# REDUCTED POINTS 53 AND 80

#IPS FICTICS THAT ARE WITHIN THE ETH1 RANGE

#PUT 53, TCP AND UDP

iptables  -t  nat  -A  PREROUTING  -i  eth0  -p  tcp  --dport  53  -j  DNAT  --to  192.168.2.4: 53

iptables  -t  nat  -A  PREROUTING  -i  eth0  -p  udp  --dport  53  -j  DNAT  --to  192.168.2.4: 53

#PUERTO 80 TCP: WEB

iptables  -t  nat  -A  PREROUTING  -i  eth0  -p  tcp  --dport  80  -j  DNAT  --to  192.168.2.5: 80
