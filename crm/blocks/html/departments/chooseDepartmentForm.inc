<?php
/**
 * @copyright 2011-2014 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 * @author Cliff Ingham <inghamn@bloomington.in.gov>
 * @param Department $this->currentDepartment
 */
use Application\Models\DepartmentTable;
use Blossom\Classes\View;

$this->template->addToAsset('scripts', JQUERY.'/jquery.min.js');
$this->template->addToAsset('scripts', BASE_URI.'/js/tickets/chooseDepartment.js');

?>
<div id="chooseDepartmentForm">
	<form method="get" action="<?php echo $_SERVER['REDIRECT_URL']; ?>">
		<fieldset><legend><?php echo $this->translate(array('labels.department', 'labels.departments', 1)); ?></legend>
			<table>
				<tr><td><label for="department_id"><?php echo $this->translate(array('labels.department', 'labels.departments', 1)); ?></label></td>
					<td><select name="department_id" id="department_id">
							<option></option>
							<?php
								$t = new DepartmentTable();
								$list = $t->find();
								foreach ($list as $department) {
									$id = $this->currentDepartment ? $this->currentDepartment->getId() : null;
									$selected = "{$department->getId()}"==$id
										? 'selected="selected"'
										: '';
									$name = View::escape($department->getName());
									echo "<option value=\"{$department->getId()}\" $selected>$name</option>";
								}
							?>
						</select>
					</td>
					<td><button type="submit">
							<span class="fa fa-check"></span>
							<?php echo $this->translate('labels.change_department'); ?>
						</button>
					</td>
				</tr>
			</table>
			<?php
				foreach ($_GET as $key=>$value) {
					if ($key !== 'department_id') {
						$value = View::escape($value);
						echo "<input type=\"hidden\" name=\"$key\" value=\"$value\" />";
					}
				}

			?>
		</fieldset>
	</form>
</div>
