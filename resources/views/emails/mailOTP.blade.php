<!DOCTYPE html>
<html>
<head>
    <title>{{ $details['title'] }}</title>
</head>
<body>
    <!-- <h1>{{ $details['title'] }}</h1>
    <p>{{ $details['body'] }}<b style="font-style: italic">{{ $details['otp'] }}</b></p> 
   
    <p>감사합니다.</p> -->

    <p>안녕하세요.</p>

    <p style="margin-top:15px">요청하신 계정의 신규 비밀번호를 안내드립니다.</p>
    <p style="font-weight:both"><b>신규 설정된 비밀번호로 BLP 시스템에 로그인 하신 후 비밀번호를 변경하여 사용하시기 바랍니다.</b></p>

    <p style="margin-top:15px">신규 비밀번호 : <b style="font-style: italic">{{ $details['otp'] }}</b></p>
    <p>BLP 시스템 URL : <a href="https://blp.spasysone.com">https://blp.spasysone.com</a> </p>

    <p style="margin-top:15px">안전한 비밀번호 보호를 위해 주기적으로(3개월에 1회이상) 비밀번호를 변경하시기 바랍니다.</p>

    <p style="margin-top:15px">감사합니다.</p>




</body>
</html>