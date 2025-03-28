<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 10px 0;
        }
        .header img {
            width: 50px;
        }
        .content {
            padding: 20px;
            text-align: center;
        }
        .content h1 {
            color: #333333;
        }
        .content p {
            color: #666666;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 20px 0;
            color: #FFF;
            background-color: #9747FFg;
            text-decoration: none;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            padding: 10px 0;
            color: #999999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://images-bulls-pay.s3.us-east-1.amazonaws.com/logo.png" alt="Bulls Pay">
        </div>
        <div class="content">
            <h1>Recuperação de Senha</h1>
            <p>Olá, {{ $name }}</p>
            <p>Você solicitou a recuperação de sua senha. Clique no botão abaixo para redefinir sua senha.</p>
            <a href="https://app.bullspay.com.br/recuperar-senha/{{ $token }}?email={{ $email }}" class="button" style="color:white">Redefinir Senha</a>
            <p>Se você não solicitou a recuperação de senha, por favor, ignore este e-mail.</p>
        </div>
        <div class="footer">
            <p>Bulls Pay &copy; Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
