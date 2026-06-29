@extends('layouts.app')

@section('title')
    Detail Sistems
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Sistems</h5>
                </div>
                @can('sistem_create')
                    <a href="{{ route('sistem.create') }}" class="btn btn-primary">Add sistems</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="form-group">
                <div class="form-group">
                    <a class="btn btn-success text-white mb-3" href="{{ route('sistem.edit') }}">
                        edit
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            @foreach ($sistems as $item)
                                <tr>
                                    <th>
                                        {{ $item->nama }}
                                    </th>
                                    <td>
                                        @if ($item->type == 'text')
                                            {{ $item->isi }}
                                        @endif
                                        @if ($item->type == 'file')
                                            <img src="{{ url('uploads/'.$item->nama.'/' . $item->isi) }}"
                                                alt="" srcset="">
                                        @endif
                                        @if ($item->type == 'number')
                                            {{ $item->isi }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
