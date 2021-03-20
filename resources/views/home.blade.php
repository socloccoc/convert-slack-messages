@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Dashboard') }}</div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form action="{{ route('convert') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>SheetId</label>
                                <input class="form-control" name="sheet_id" value="1Li8k2NUF67YIS6hr943ybABs2kBcDeO9zuDSdz0s50A" />
                            </div>
                            <div class="form-group">
                                <label>Sheet Name</label>
                                <input class="form-control" name="sheet_name" value="シート1" />
                            </div>
                            <div class="form-group">
                                <label>Servers</label>
                                <select name="server" class="custom-select">
                                    <option value="dam" selected>Dam</option>
                                    <option value="dwjp">Dwjp</option>
                                    <option value="jpstore">Jpstore</option>
                                    <option value="baas">Baas</option>
                                    <option value="saas">Saas</option>
                                    <option value="sumo">Sumo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Body</label>
                                <textarea class="form-control" rows="15" name="body"></textarea>
                            </div>
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary">Convert</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
