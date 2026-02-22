package security

import (
	"crypto/rand"
	"fmt"
	"math/big"
)

// GenerateOTP creates a 6-digit numeric OTP.
func GenerateOTP() (string, error) {
	otp := ""
	for i := 0; i < 6; i++ {
		num, err := rand.Int(rand.Reader, big.NewInt(10))
		if err != nil {
			return "", err
		}
		otp += fmt.Sprintf("%d", num)
	}
	return otp, nil
}
