
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ url('/') }}" class="brand-link">
        <img src="{{ auth()->user()->getImageUrlAttribute() }}" alt="{{auth()->user()->name}}" class="brand-image img-circle elevation-3"
            style="opacity: .8">
        <span class="brand-text font-weight-light">{{ auth()->user()->name }}</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
       
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
               <!-- <li class="nav-item has-treeview menu-open">
                    <a href="#" class="nav-link active">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                            Manage
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('view role')
                        <li class="nav-item">
                            <a href="{{ route('admin.role.index') }}" class="nav-link">
                                <i class="fas fa-file-alt nav-icon"></i>
                                <p>Role & Permission</p>
                            </a>
                        </li>
                        @endcan 
                        @can('view user')
                        <li class="nav-item">
                            <a href="{{ url('admin/user') }}" class="nav-link">
                                <i class="fas fa-users nav-icon"></i>
                                <p>Users</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li> -->
                <li class="nav-item has-treeview menu-open">
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('admin') }}" class="nav-link {{ Route::is('admin.') || Route::is('admin.')  ? 'active' : '' }}">
                                <i class="fas fa-home nav-icon"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            @if(isset(auth()->user()->linnworks_token()->token))
                            <a href="{{ url('admin/pickingwaves') }}" class="nav-link {{ Route::is('admin.pickingwaves.*') || Route::is('admin.picklist.*')  ? 'active' : '' }}">
                                <i class="fas fa-warehouse nav-icon"></i>
                                <p>Picklist</p>
                            </a>
                            @endif
                        </li>
                        <li class="nav-item">
                            @if(isset(auth()->user()->linnworks_token()->token))
                            <a href="{{ url('admin/packingwaves') }}" class="nav-link {{ Route::is('admin.packingwaves.*') || Route::is('admin.packlist.*')  ? 'active' : '' }}">
                                <i class="fas fa-warehouse nav-icon"></i>
                                <p>Packlist</p>
                            </a>
                            @endif
                        </li>

                        @can('view user')
                        <li class="nav-item">
                            <a href="{{ url('admin/user') }}" class="nav-link {{ Route::is('admin.user.*') || Route::is('admin.user.*') || Route::is('admin.profile.*') ? 'active' : '' }}">
                                <i class="fas fa-users nav-icon"></i>
                                <p>Team Members</p>
                            </a>
                        </li>
                        @endcan

                        <li class="nav-item has-treeview {{ Route::is('admin.role.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ 
                                Route::is('admin.role.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>
                                    Settings
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview" style="background-color: black;">

                                @can('view role')
                                <li class="nav-item">
                                    <a href="{{ route('admin.role.index') }}" class="nav-link {{ Route::is('admin.role.*') ? 'active' : '' }}">
                                        <i class="fas fa-file-alt nav-icon"></i>
                                        <p>Role & Permission</p>
                                    </a>
                                </li>
                                @endcan

                                @can('view Print Buttons')
                                <li class="nav-item">
                                    <a href="{{ route('admin.print_buttons.index') }}" class="nav-link {{ Route::is('admin.print_buttons.*') ? 'active' : '' }}">
                                        <i class="fas fa-print nav-icon"></i>
                                        <p>Dynamic Print Buttons</p>
                                    </a>
                                </li>
                                @endcan

                                @can('view folders setting')
                                <li class="nav-item">
                                    <a href="{{ route('admin.setting.folders') }}" class="nav-link {{ Route::is('admin.setting.folders') ? 'active' : '' }}" id="popup-modal-button">
                                        <i class="fas fa-folder-open nav-icon"></i>
                                        <p>Folder Setting</p>
                                    </a>
                                </li>
                                @endcan


                                <li class="nav-item">
                                    <a href="{{ route('admin.profile.printers_zone') }}" class="nav-link {{ Route::is('admin.profile.printers_zone') ? 'active' : '' }}" id="popup-modal-buttonUserRole">
                                        <i class="fas fa-print nav-icon"></i>
                                        <p>Print Zone</p>
                                    </a>
                                </li>
                                <!-- <li class="nav-item">
                                    <a href="{{ route('admin.packlist.ajax.packingwavesCompletedNotificationSend') }}" class="nav-link" id="packingwavesCompletedNotification">
                                        <i class="fas fa-envelope nav-icon"></i>
                                        <p>Packingwaves</p>
                                        <p>Completed Notification</p>
                                    </a>
                                </li> -->
                                
                                <li class="nav-item">
                                    <a href="{{ env('LINNWORKS_INSTALLATION_URL'), 'https://apps.linnworks.net/Authorization/Authorize/9a50e415-9916-4a50-8c57-b13a73b33216' }}?Tracking={{auth()->user()->createToken('authToken')->accessToken}}" class="nav-link" target="_blank">
                                        <i class="fas fa-plus nav-icon"></i>
                                        <p>Get token</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>