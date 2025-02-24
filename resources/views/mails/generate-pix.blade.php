<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <title>Pague seu PIX aqui!</title>

    <style>
        * {
            font-family: 'Poppins', Arial, sans-serif;
            color: #3A3B42;
        }

        .email {
            width: 100%;
            padding: 20px;
            background: #EBEFFA;
        }

        figure {
            margin: 0px;
        }

        header figure img {
            width: 120px;
            max-width: 100%;
        }

        header h2 {
            font-size: 25px;
        }

        header p {
            font-size: 16px;
            font-weight: 500;
        }

        .infos {
            width: 100%;
            margin-top: 30px;
        }

        .infos .step {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .infos .step .step-number {
            color: white;
            background: #12B979;
            height: 20px;
            width: 20px;
            padding: 8px 0px 8px 12px;
            border-radius: 10px;
            display: flex;
            text-align: center;
        }

        .infos .step .step-number p {
            font-size: 12px;
            text-align: center;
        }

        .infos .step p {
            font-size: 14px;
            margin-left: 10px;
        }

        .infos .payment-infos {
            margin-top: 30px;
        }

        .infos .payment-infos p {
            font-size: 16px;
            font-weight: 500;
        }

        .infos .payment-infos figure img {
            width: 300px;
            max-width: 90%;
        }

        .payment-description {
            width: 100%;
            margin-top: 30px;
            border-top: 1px solid #3A3B42;
        }

        .payment-description .line {
            display: flex;
        }

        .payment-description .description {
            margin-right: 10px;
            min-width: 120px;
        }

        .payment-description .description h2 {
            font-size: 12px;
            font-weight: 600;
            margin-top: 20px;
        }

        .payment-description .description p {
            font-size: 12px;
            font-weight: 500;
            margin-top: 0px;
        }
    </style>
</head>

<body>
    <div class="email">
        <header>
            <figure>
                <img src="https://images-bulls-pay.s3.us-east-1.amazonaws.com/logo.png">
            </figure>

            <h2>{{ $name }}, você está quase lá!</h2>

            <p>Você está a poucos passos de concluir o seu pagamento. Realize agora mesmo seguindos esses passos:</p>
        </header>

        <div class="infos">
            <div class="step">
                <div class="step-number">1</div>

                <p>Abra o aplicativo do seu banco de pagamentos.</p>
            </div>

            <div class="step">
                <div class="step-number">2</div>

                <p>Busque a opção de pagar com PIX.</p>
            </div>

            <div class="step">
                <div class="step-number">1</div>

                <p>Copie e cole o código abaixo, ou leia o QR Code.</p>
            </div>

            <div class="payment-infos">
                <p>Copie e Cole o código Pix no seu banco para realizar o pagamento:</p>
                <p><strong>{{ $pixCode }}</strong></p>

                <p>Ou escaneie o QR Code abaixo:</p>
                <figure>
                    <img src="https://quickchart.io/qr?text={{ $pixCode }}" alt="QR Code Pix" />
                </figure>
            </div>
        </div>

        <div class="payment-description">
            <div class="line">
                <div class="description">
                    <h2>ID da transação</h2>
                    <p>{{ $id }}</p>
                </div>

                <div class="description">
                    <h2>Valor da transação</h2>
                    <p>R$ {{ $price }}</p>
                </div>
            </div>

            <div class="line">
                <div class="description">
                    <h2>Método de pagamento</h2>
                    <p>Pix</p>
                </div>

                <div class="description">
                    <h2>Contato do produtor</h2>
                    <p>{{ $support }}</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
