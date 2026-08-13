<script type="text/javascript">
    function add_allowance() {
        var basic_pay = document.getElementById('basic').value || 0;
        var allowance_amount = document.getElementsByName('allowance_amount[]');
        var tax = document.getElementById('tax').value;
        if (tax === '') { tax = 0; }

        var total_allowance = 0;
        var deduction_amount = document.getElementsByName('deduction_amount[]');
        var total_deduction = 0;

        for (var i = 0; i < allowance_amount.length; i++) {
            var inpvalue = allowance_amount[i].value === '' ? 0 : allowance_amount[i].value;
            total_allowance += parseFloat(inpvalue);
        }
        for (var j = 0; j < deduction_amount.length; j++) {
            var inpdvalue = deduction_amount[j].value === '' ? 0 : deduction_amount[j].value;
            total_deduction += parseFloat(inpdvalue);
        }

        var gross_salary = parseFloat(basic_pay) + parseFloat(total_allowance) - parseFloat(total_deduction);
        var net_salary = gross_salary - parseFloat(tax);

        document.getElementById('total_allowance').value = total_allowance.toFixed(2);
        document.getElementById('total_deduction').value = total_deduction.toFixed(2);
        document.getElementById('gross_salary').value = gross_salary.toFixed(2);
        document.getElementById('net_salary').value = net_salary.toFixed(2);
    }

    function add_more() {
        var table = document.getElementById('tableID');
        var id = table.rows.length;
        var row = table.insertRow(table.rows.length);
        row.id = 'row' + id;
        row.innerHTML = "<td><input type='hidden' name='allowance_prev_id[]' value='0'><input type='text' class='form-control' name='allowance_type[]' placeholder='Type'></td>" +
            "<td><input type='text' class='form-control' name='allowance_amount[]' value='0'></td>" +
            "<td><button type='button' onclick='delete_row(" + id + ")' class='btn btn-xs btn-danger'><i class='fa fa-remove'></i></button></td>";
    }

    function delete_row(id) {
        var row = document.getElementById('row' + id);
        if (row) { row.innerHTML = ''; }
    }

    function add_more_deduction() {
        var table = document.getElementById('tableID2');
        var id = table.rows.length;
        var row = table.insertRow(table.rows.length);
        row.id = 'deduction_row' + id;
        row.innerHTML = "<td><input type='hidden' name='deduction_prev_id[]' value='0'><input type='text' class='form-control' name='deduction_type[]' placeholder='Type'></td>" +
            "<td><input type='text' class='form-control' name='deduction_amount[]' value='0'></td>" +
            "<td><button type='button' onclick='delete_deduction_row(" + id + ")' class='btn btn-xs btn-danger'><i class='fa fa-remove'></i></button></td>";
    }

    function delete_deduction_row(id) {
        var row = document.getElementById('deduction_row' + id);
        if (row) { row.innerHTML = ''; }
    }

    document.getElementById('employeeform').addEventListener('submit', function (event) {
        var net = document.getElementById('net_salary').value;
        if (net === '') {
            alert('Net salary should not be empty');
            document.getElementById('net_salary').focus();
            event.preventDefault();
        }
    });
</script>
