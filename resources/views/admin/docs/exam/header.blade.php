
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
    <style>
        body {font-size:14px;}
    </style>
</head>
<body>
<p align=center>МІНІСТЕРСТВО ОХОРОНИ ЗДОРОВЯ УКРАЇНИ </p>
<p align=center><b><u>ДВНЗ «Тернопільський державний медичний університет імені І.Я. Горбачевського МОЗ України</u></b></p>
<table class=guestbook width=625 align=center cellspacing=0 cellpadding=3 border=0>
    <tr>
        <td width=80%> Факультет <u>{{ $this['department'] }}</u></td><td>Група_<u>{{ $this['group'] }}</u>_</td>
    </tr>
    <tr>
        <td width=80%> <u>{{ $this['dataEachOfFile']->EduYear }} / {{ ($this['dataEachOfFile']->EduYear + 1)}}</u> навчальний рік</td><td>Курс _<u>{{ $this['semester'] }}</u>___</td>
    </tr>
    <tr>
        <td width=80%>  Спеціальність <u>{{ $this['speciality'] }}</u></td><td></td>
    </tr>
</table>
<p align=center>ЕКЗАМЕНАЦІЙНА ВІДОМІСТЬ №____ </p>
<p>З <u>{{$this['dataEachOfFile']->ModuleNum}}. {{$this['dataEachOfFile']->NameDiscipline}}</u> - <u>{{$this['dataEachOfFile']->NameModule}}</u></p>
<p>За _<u>{{$this['dataEachOfFile']->Semester}}</u>___ навчальний семестр, екзамен <u>_{{((Session::has('date')) ? Session::get('date') : $this['date'])}}___</u></p>
<table class=guestbook width=600 align=center cellspacing=0 cellpadding=3 border=1>
    <tr>
        <td width=10%>
            <b>№ п/п</b>
        </td>
        <td width=50%>
            <b>Прізвище, ім'я по-батькові</b>
        </td>
        <td width=10%>
            <b>№ індиві-дуального навч. плану</b>
        </td>
        <td width=10%>
            <b>Кількість балів</b>
        </td>
    </tr>
        