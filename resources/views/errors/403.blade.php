@extends('errors.layout')
@section('code', '403')
@section('title', 'لا تملك صلاحية فتح هذه الصفحة')
@section('message', 'إن كنت تظن أن هذا خطأ فراجع مدير الصيدلية.')
@section('actions')
    <a href="/">الصفحة الرئيسية</a>
@endsection
