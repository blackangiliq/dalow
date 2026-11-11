@extends('errors.layout')
@section('code', '503')
@section('title', 'الموقع في صيانة قصيرة')
@section('message', 'نحدّث الموقع الآن ونعود خلال دقائق. يمكنك الاتصال بالصيدلية لطلب دوائك.')
@section('actions')
    <a href="javascript:location.reload()">أعد المحاولة</a>
@endsection
