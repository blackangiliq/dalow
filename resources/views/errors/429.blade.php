@extends('errors.layout')
@section('code', '429')
@section('title', 'محاولات كثيرة')
@section('message', 'أرسلت طلبات كثيرة في وقت قصير. انتظر دقيقة ثم حاول مرة أخرى.')
@section('actions')
    <a href="javascript:history.back()">رجوع</a>
@endsection
