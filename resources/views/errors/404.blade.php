@extends('errors.layout')
@section('code', '404')
@section('title', 'هذه الصفحة غير موجودة')
@section('message', 'قد يكون الرابط قديماً أو المنتج لم يعد متوفراً. ابحث عن ما تريده من الرئيسية.')
@section('actions')
    <a href="/">الصفحة الرئيسية</a><a class="ghost" href="/products">كل المنتجات</a>
@endsection
