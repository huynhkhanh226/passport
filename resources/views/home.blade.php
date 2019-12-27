@extends('layouts.app')

@section('content')
    <div class="container" id="app">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Dashboard</div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        You are logged in!
                        <div class="row form-group">
                            <div class="col-md-12">
                                <passport-clients></passport-clients>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-12">
                                <passport-authorized-clients></passport-authorized-clients>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-12">
                                <passport-personal-access-tokens></passport-personal-access-tokens>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-12 text-center">
                                <a href="{{ route('logout') }}" class="btn btn-primary"
                                   onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                      style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
