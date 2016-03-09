@extends('layouts.main')
@section('content')
          
          <a href="{{ $infusionsoft->getAuthorizationUrl() }}" class="btn btn-primary"> Login to InfusionSoft </a>
        
@stop      