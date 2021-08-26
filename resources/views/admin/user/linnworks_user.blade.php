@extends('admin.layouts.master')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12" id="message">
            @include('message.alert')
        </div>

        <div class="row col-sm-12 page-titles">
            <div class="col-lg-5 p-b-9 align-self-center text-left  " id="list-page-actions-container">
                <div id="list-page-actions">
                    <!--ADD NEW ITEM-->
                    <!-- @can('create user')
                    <a href="{{ route('admin.user.create') }}" class="btn btn-danger btn-add-circle edit-add-modal-button js-ajax-ux-request reset-target-modal-form" id="popup-modal-buttonUserRole">
                        <span tooltip="Create new team member." flow="right"><i class="fas fa-plus"></i></span>
                    </a>
                    @endcan -->
                    <!--ADD NEW BUTTON (link)-->
                </div>
            </div>
            <div class="col-lg-7 align-self-center list-pages-crumbs text-right" id="breadcrumbs">
                <h3 class="text-themecolor">Team Members</h3>
                <!--crumbs-->
                <ol class="breadcrumb float-right">
                    <li class="breadcrumb-item">App</li>    
                    <li class="breadcrumb-item  active active-bread-crumb ">Team Members</li>
                </ol>
                <!--crumbs-->
            </div>
            
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Team Members List</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body ">
                    <div class="table-responsive list-table-wrapper">
                        <table class="table table-hover" id="datatableUser">
                            <thead>
                                <tr>
                                    <th>Linnworks UserId</th>
                                    <th>Email</th>
                                    <th>Super Admin</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($linnworks_users as $user)
                                <tr>
                                    <td>{{ $user['fkUserId'] }}</td>
                                    <td>{{ $user['EmailAddress'] }}</td>
                                    <td>@if($user['SuperAdmin']==1) Super Admin @else Staff @endif</td>
                                    <td>
                                        @if(in_array($user['EmailAddress'], $users))
                                            @php
                                                $linnworks_email = $user['EmailAddress'];
                                                $user_details = App\User::whereHas('linnworks', function($q) use ($linnworks_email) { $q->where('linnworks_email', $linnworks_email); })->first();
                                            @endphp
                                            @can('edit user')
                                            <a href="{{ route('admin.user.edit', ['user' => $user_details->id]) }}" 
                                                class="btn btn-success btn-sm float-left mr-3"  id="popup-modal-buttonUserRole">
                                                <span tooltip="Edit" flow="left"><i class="fas fa-edit"></i></span>
                                            </a>
                                            @endcan 
                                            @can('delete user')
                                            <form method="post" class="float-left delete-formUserRole"
                                                action="{{ route('admin.user.destroy', ['user' => $user_details->id ]) }}">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <span tooltip="Delete" flow="right"><i class="fas fa-trash-alt"></i></span>
                                                </button>
                                            </form>
                                            @endcan 
                                        @else
                                            <form method="post" class="float-left"
                                            action="{{ route('admin.linnworks-user.create') }}" id="popup-modal-form">
                                                @csrf
                                                @method('post')
                                                <input type="hidden" name="email" value="{{ $user['EmailAddress'] }}">
                                                <input type="hidden" name="user_id" value="{{ $user['fkUserId'] }}">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <span tooltip="Create User" flow="right"><i class="fas fa-plus"></i></span>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5">There is no user.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- /.card-body -->
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function datatables() {
        var table = $('#datatableUser').DataTable({
            dom: 'Bfrtip',
            buttons: []
        });
    }
    datatables();

    /*For user status change*/
    function funChangeStatus(id,status) {
        $("#pageloader").fadeIn();
        $.ajax({
          url : '{{ route('admin.user.ajax.change_status') }}',
          data: {
            "_token": "{{ csrf_token() }}",
            "id": id,
            "status": status
            },
          type: 'get',
          dataType: 'json',
          success: function( result )
          {
            datatables();
            $("#pageloader").hide();
          }
        });
    }

</script>
@endsection