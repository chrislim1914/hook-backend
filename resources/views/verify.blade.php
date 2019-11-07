<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Welcome Mail</title>
    <link href="{{ ('/css/style.css') }}" rel="stylesheet"> 
</head>
<body>
    <!-- header -->
    <div class="header">
        <img src="/img/hook.png">
    </div>

    <!-- box -->
    <div class="box">
        <h1>Verify this Email Address</h1>
        <h3>Hi {{ $username }},</h3>
        <h3>to verify your email, please click the button below</h3>
        <a  href="{{ $url }}" class="verify">VERIFY EMAIL</a>
        <hr>
        <p>or copy this link and paste in your browser</p>
        <a href=>
            {{ $username }}
        </a>
        <br />
        <br />
        <p>cheers,</p>
        <p>geeknation team</p>
    </div>

</body>
</html>