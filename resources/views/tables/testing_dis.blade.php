<div class="table-responsive">
	<table id="{{ $div }}"  cellspacing="1" cellpadding="3" class="tablehead table table-striped table-bordered">
		<thead>
			<tr class="colhead">
                <th>#</th>
                    @if (session('filter_partner') == null)
                     <th>Partner</th>
                    @else
                     <th>County</th>
                    @endif
                    <th>Positive</th>
                    <th>Negative</th>
                    <th>Yield</th>

			</tr>
		</thead>
		<tbody>
            <?php
			$i=0;
            $calc_percentage = function($num, $den, $roundby=1)
            {
                if(!$den){
                    $val = null;
                }else{
                    $val = round(($num / $den * 100), $roundby) . "%";
                }
                return $val;
            };
            $x = (count($rows)-1);
            ?>
            @foreach($rows as $key => $row)
                            

				<tr>
					<td> {{ $key +1 }} </td>
					<td> {{ $row->name ?? '' }} </td>
					<td> {{ (int) $row->pos ?? '' }} </td>
					<td> {{ (int) ($row->tests - $row->pos) ?? '' }} </td>
					<td> {{ $calc_percentage($row->pos, $row->tests) ?? '' }} </td>

				</tr>
                
                <?php
                $i++;
                ?>
			@endforeach
		</tbody>	
	</table>
</div>


<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		$('#{{ $div }}').DataTable({
			dom: '<"btn"B>lTfgtip',
			// responsive: true,
			paging:false,
			buttons : [
				{
				  text:  'Export to CSV',
				  extend: 'csvHtml5',
				  title: 'Download'
				},
				{
				  text:  'Export to Excel',
				  extend: 'excelHtml5',
				  title: 'Download'
				}
			]
		});
	});
</script>
