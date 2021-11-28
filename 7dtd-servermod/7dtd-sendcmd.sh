#!/usr/bin/expect
set timeout 5
set password [lindex $argv 0]
set command [lindex $argv 1]

spawn telnet 127.0.0.1 8081
expect "Please enter password:"
send "$password\r"; sleep 1;
send "$command\r"
send "exit\r";
expect eof;
send_user "Sent command to 7DTD: $command\n"
