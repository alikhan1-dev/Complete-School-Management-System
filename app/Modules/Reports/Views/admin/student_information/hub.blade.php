<div class="box box-primary border0 mb0">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.student_information_report') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            @if(!empty($canStudentReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/studentreport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.student_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canClassSectionReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/classsectionreport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.class_section_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canGuardianReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/guardianreport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.guardian_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canStudentHistory))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/admissionreport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.student_history') }}
                    </a>
                </div>
            @endif
            @if(!empty($canLoginCredential))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/logindetailreport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.student_login_credential') }}
                    </a>
                </div>
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/parentlogindetailreport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.parent_login_credential') }}
                    </a>
                </div>
            @endif
            @if(!empty($canClassSubjectReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/class_subject') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.class_subject_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canAdmissionReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/admission_report') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.admission_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canSiblingReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/sibling_report') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.sibling_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canStudentProfile))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/student_profile') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.student_profile') }}
                    </a>
                </div>
            @endif
            @if(!empty($canGenderRatio))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/boys_girls_ratio') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.student_gender_ratio_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canTeacherRatio))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/student_teacher_ratio') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.student_teacher_ratio_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canOnlineAdmissionReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/online_admission_report') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.online_admission_report') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
