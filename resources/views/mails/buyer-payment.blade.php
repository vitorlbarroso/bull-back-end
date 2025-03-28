<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <title>A sua compra foi processada com sucesso!</title>

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

            <h2>{{ $name }}, o seu pagamento foi realizado com sucesso!</h2>

            <p>Falta muito pouco para acessar os materiais adquiridos, para isso, siga os passos abaixo.</p>
        </header>

        <div class="infos">
            <div class="step">
                <div class="step-number">1</div>

                <p>Copie o código do seu pedido: {{ $paymentCode }}</p>
            </div>

            <div class="step">
                <div class="step-number">2</div>

                <p>Envie o seu código com o assunto <b>"Acesso compra - {{ $paymentCode }}"</b>: {{ $supportEmail }}</p>
            </div>

            <div class="step">
                <div class="step-number">3</div>

                <p>Pronto, agora é só você aproveitar os seus materiais! 🙂</p>
            </div>
        </div>
    </div>
</body>
</html>
