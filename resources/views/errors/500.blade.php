@extends('errors.layout')
@section('code', '500')
@section('title', 'حدث خطأ عندنا')
@section('message', 'المشكلة من الموقع وليست منك، وسُجلت لنصلحها. حاول بعد قليل، أو اتصل بالصيدلية لإتمام طلبك.')
@section('actions')
    <a href="/">الصفحة الرئيسية</a><a class="ghost" href="javascript:location.reload()">أعد المحاولة</a>
@endsection
