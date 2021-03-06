<?php
/**
 * @copyright 2011-2014 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 * @author Cliff Ingham <inghamn@bloomington.in.gov>
 * @param ClientList $this->clientList
 */
use Application\Models\Person;
use Blossom\Classes\View;
?>
<div class="clientList">
	<h2><?php
			echo $this->translate(array('labels.client', 'labels.clients', 2));
			if (Person::isAllowed('clients','update')) {
				$helper = $this->template->getHelper('buttonLink');
				echo $helper->buttonLink(
					BASE_URI.'/clients/update',
					$this->translate('labels.add'),
					'add'
				);
			}
		?>
	</h2>
	<p><?php echo $this->translate('text.clients_description'); ?>	</p>
	<?php
		foreach ($this->clientList as $client) {
			$editButton   = '';
			$deleteButton = '';
			if (Person::isAllowed('clients','update')) {
				$editButton = $helper->buttonLink(
					BASE_URI.'/clients/update?client_id='.$client->getId(),
					$this->translate('labels.edit'),
					'edit'
				);
				$deleteButton = $helper->buttonLink(
					BASE_URI.'/clients/delete?client_id='.$client->getId(),
					$this->translate('labels.delete'),
					'delete'
				);
			}
			$url         = $client->getURL();
			$contact     = $client->getContactPerson();
			$name        = View::escape($client->getName());
			$contactName = View::escape($contact->getFullname());
			echo "
			<div>
				<h3>$name $editButton $deleteButton</h3>
				<table>
					<tr><th>{$this->translate(array('labels.contactMethod','labels.contactMethods',1))}</th>
						<td>{$client->getContactMethod()}</td>
					</tr>
					<tr><th>{$this->translate('labels.api_key')}</th>
						<td>{$client->getApi_key()}</td>
					</tr>
					<tr><th>{$this->translate('labels.url')}</th>
						<td><a href=\"$url\">$url</a></td>
					</tr>
					<tr><th>{$this->translate('labels.contact')}</th>
						<td><a href=\"{$contact->getURL()}\">$contactName</a></td>
					</tr>
				</table>
			</div>
			";
		}
	?>
</div>
