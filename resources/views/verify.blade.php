<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Welcome Mail</title>
    <style>
        body {
            margin: 0 auto;
            padding: 0;
            background-color: #F5F9FA;
            color: #ffffff;
            font-size: medium;
            font-family: 'Lucida Sans', 'Lucida Sans Regular', 'Lucida Grande', 'Lucida Sans Unicode', Geneva, Verdana, sans-serif;
        }

        .header {
            display: flex;
            width: 100%;
            height: 100px;
        }

        .header img {
            margin: auto;  /* Magic! */
        }

        .box {
            margin: 0 auto;
            width: 60%;
            background-color: #ffffff;
            color: #000000;
            padding: 10px 20px;
            border: 1px solid #bdbbbd;
            border-top: 1px solid #01A99E;
            text-align: center;
            margin-bottom: 20px;
        }

        h1, h3 {
            font-weight: 500;
            text-align: center !important;
        }

        .verify {
            margin: 20px 0;  
            background-color: #01A99E;
            color: #ffffff !important;
            font-size: large;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 400;
            text-decoration: none;
        }

        .verify a {
            color: #ffffff;
            text-decoration: none;
        }

        a {
            font-size: 10px;
        }

        a p {
            word-break: break-all
        }

        hr {
            margin: 30px 0;
            border: 0; 
            height: 1px; 
            background-image: linear-gradient(to right, 
                                    rgba(1, 169, 158, 0), 
                                    rgba(1, 169, 158, 0.75), 
                                    rgba(1, 169, 158, 0)); 
        }

    </style>
</head>
<body>
    <!-- header -->
    <div class="header">
        <img src="http://api.allgamegeek.com/img/hook.png">
    </div>

    <!-- box -->
    <div class="box">
        <h1>Verify this Email Address</h1>
        <h3>Hi {{ $username }},</h3>
        <h3>to verify your email, please click the button below</h3>
        <a  href="{{ $url }}" class="verify">VERIFY EMAIL</a>
        <hr>
        <p>or copy this link and paste in your browser</p>
        <a href="{{ $url }}">
            <p>{{ $url }}</p>            
        </a>
        <br />
        <br />
        <p>cheers,</p>
        <p>geeknation team</p>
    </div>

</body>
</html>