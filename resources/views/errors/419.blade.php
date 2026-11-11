@extends('errors.layout')
@section('code', '419')
@section('title', 'انتهت مدة الصفحة')
@section('message', 'بقيت الصفحة مفتوحة طويلاً. ارجع وحدّثها ثم أعد المحاولة، ولن تضيع سلتك.')
@section('actions')
    <a href="javascript:history.back()">رجوع</a><a class="ghost" href="/">الصفحة الرئيسية</a>
@endsection
