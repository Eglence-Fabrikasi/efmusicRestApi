<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancel</title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
        integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css"
        integrity="sha512-HK5fgLBL+xu6dm/Ii3z4xhlSUyZgTT9tuc/hSrtw6uzJOvgRr2a9jyxxT1ely+B+xFAmJKVSTbpM/CuL7qxO8w=="
        crossorigin="anonymous" />

    <style>
        body {
            text-align: center;
            background-color: #F5F5F5;
        }

        .container {
            height: 100vh;
            background-color: #FFFFFF;
        }

        .row {
            -webkit-box-align: center;
            -webkit-align-items: center;
            -ms-flex-align: center;
            align-items: center;
            height: 100%;
            -webkit-box-shadow: 0 0px 10px rgb(85 85 85 / 20%) !important;
            box-shadow: 0 0px 10px rgb(85 85 85 / 20%) !important;
        }

        #error-icon {
            font-size: 5em;
            color: #C82333;
        }

        #error-text {
            font-size: 2em;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col">
                <div id="error-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div id="error-text">
                    <p>Ödeme esnasında bir hata oluştu!</p>
                    <p>Yönlendiriliyorsunuz...</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<script>
setTimeout(function(){
    document.location.href="https://app.eglencefabrikasi.com/content/albums";
   // 5 sn
}, 3000);
</script>