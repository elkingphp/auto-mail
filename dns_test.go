package main

import (
	"fmt"
	"net"
	"os"
)

func main() {
	host := "oracle"
	if len(os.Args) > 1 {
		host = os.Args[1]
	}
	ips, err := net.LookupIP(host)
	if err != nil {
		fmt.Printf("LookupIP failed for %s: %v\n", host, err)
		os.Exit(1)
	}
	for _, ip := range ips {
		fmt.Printf("%s IN A %s\n", host, ip.String())
	}
}
