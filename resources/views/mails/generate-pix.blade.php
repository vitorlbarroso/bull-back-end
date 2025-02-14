<!DOCTYPE html>
<html>
<head>
    <title>Pague seu PIX aqui!</title>
</head>
<body>
<h2>Seu pagamento via PIX foi gerado e está disponível para pagamento!</h2>

<p>Escaneie o QR Code abaixo:</p>
<img src="https://quickchart.io/qr?text={{ $pixCode }}" alt="QR Code Pix" />

<p>Ou Copie e Cole o código Pix no seu banco para realizar o pagamento:</p>
<p><strong>{{ $pixCode }}</strong></p>

<p>Obrigado por sua compra!</p>
</body>
</html>
