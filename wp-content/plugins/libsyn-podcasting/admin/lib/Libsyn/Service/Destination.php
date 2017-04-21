<?php
namespace Libsyn\Service;
/*
	This class is used to import 3rd party podcast feeds into the libsyn network.
	For other 3rd party integrations please see Integration class.
*/
class Destination extends \Libsyn\Service {

    /**
     * Formats the given array of destinations into
	 * JSON encoded array then updated given post meta
	 * with data for default set of selected destiations.
     * 
     * @param <array> $destinations 
     * @param <int> $post_id 
     * 
     * @return <mixed>
     */
	public function formatDestinationFormData($destinations, $post_id) {
		global $wpdb;
		if(!empty($destinations->destinations)) {
			$destinations_json = array();
			foreach($destinations->destinations as $destinaiton_id => $working_destination) {
				$destinations_json["libsyn-post-episode-advanced-destination-".$working_destination->destination_id."-release-time"] = "";
				$destinations_json["libsyn-post-episode-advanced-destination-".$working_destination->destination_id."-expiration-time"] = "";
				// $destinations_json["set_release_scheduler_advanced_release_lc__".$working_destination->destination_id."-0"] = "checked";
				// $destinations_json["set_release_scheduler_advanced_release_lc__".$working_destination->destination_id."-0"] = "";
				$destinations_json["libsyn-advanced-destination-checkbox-".$working_destination->destination_id] = "checked";
			}
		}
		update_post_meta($post_id, 'libsyn-post-episode-advanced-destination-form-data', json_encode($destinations_json));
		
	}

	
	/**
	 * Formats the destination table html data
	 * @param array $destinations 
	 * @param int $post_id 
	 * @return string
	 */
	public function formatDestinationsTableData($destinations, $post_id){
		if(isset($post_id)&&!empty($post_id)) $published_destinations = get_post_meta($post_id, 'libsyn-destination-releases', true);
			else $published_destinations = '';
		//remove Wordpress Destination
		foreach($destinations->destinations as $key => $destination)
			if($destination->destination_type==='WordPress') 
				unset($destinations->destinations->{$key});
		foreach ((array) $destinations->destinations as $destination){
			$destination->cb = '<input type=\"checkbox\" value=\"' . $destination->destination_id . '\" />';
			
			if(!empty($published_destinations)){
				foreach($published_destinations as $published_destination){
					if(isset($published_destination->destination_id)) {
						if($published_destination->destination_id===$destination->destination_id){
							$destination->published_status = '<div id=\"destination_release_published_state_'.$destination->destination_id.'\">Status: '.ucfirst($published_destination->release_state).'</div><div id=\"destination_release_published_date_'.$destination->destination_id.'\">'.date("M j, Y, g:ia", strtotime($published_destination->release_date)).'</div>';
						}
					}
				}
				if(!isset($destination->published_status)) {
					$destination->published_status = '<div id=\"destination_release_published_state_'.$destination->destination_id.'\">Status: Unreleased</div>';
				}
			}
			$destination->release_date = '
				<div class=\"form-field-wrapper\" id=\"set_release_scheduler_advanced_release_lc__' . $destination->destination_id . '-wrapper\">
					<label for=\"set_release_scheduler_advanced_release_lc__' . $destination->destination_id . '\" class=\"screen-hidden optional\">Release Date
					</label>
					<div class=\"form-field radio-options\">
						<label>
							<input type=\"radio\" name=\"set_release_scheduler_advanced_release_lc__' . $destination->destination_id . '\" id=\"set_release_scheduler_advanced_release_lc__' . $destination->destination_id . '-0\" value=\"0\" checked=\"checked\" class=\"libsyn-form-element show-hide-selector\" data-show-on-value=\"2\" data-toggle-id=\"form-field-wrapper_release_scheduler_advanced_release_lc__' . $destination->destination_id . '\" autocomplete=\"off\">
								Release immediately on publish
						</label>
						<br>
						<label>
							<input type=\"radio\" name=\"set_release_scheduler_advanced_release_lc__' . $destination->destination_id . '\" id=\"set_release_scheduler_advanced_release_lc__' . $destination->destination_id . '-2\" value=\"2\" class=\"libsyn-form-element show-hide-selector\" data-show-on-value=\"2\" data-toggle-id=\"form-field-wrapper_release_scheduler_advanced_release_lc__' . $destination->destination_id . '\" autocomplete=\"off\">
								Set new release date
						</label>
					</div>
				</div>
				<div class=\"form-field-wrapper\" id=\"form-field-wrapper_release_scheduler_advanced_release_lc__' . $destination->destination_id . '\" style=\"display: none;\">
					<div class=\"form-field date-time\">
						<input type=\"hidden\" name=\"release_scheduler_advanced_release_lc__' . $destination->destination_id . '\" value=\"\" data-match-source=\"release_dates\" id=\"release_scheduler_advanced_release_lc__' . $destination->destination_id . '\" />
						<div class=\"form-field-wrapper\" id=\"release_scheduler_advanced_release_lc__' . $destination->destination_id . '_date-wrapper\">
							<div class=\"form-field\">
								<input type=\"text\" name=\"release_scheduler_advanced_release_lc__' . $destination->destination_id . '_date\" id=\"release_scheduler_advanced_release_lc__' . $destination->destination_id . '_date\" value=\"\" style=\"width:auto\" size=\"10\" />
							</div>
						</div>
						<div class=\"libsyn-advanced-release-time\">
							<select name=\"release_scheduler_advanced_release_lc__' . $destination->destination_id . '_time_select\" id=\"release_scheduler_advanced_release_lc__' . $destination->destination_id . '_time_select_select-element\"></select>
							<input type=\"hidden\" value=\"' . get_post_meta( $object->ID, 'libsyn-post-episode-advanced-destination-' . $destination->destination_id . '-release-time', true ) . '\" name=\"libsyn-post-episode-advanced-destination-' . $destination->destination_id . '-release-time\" id=\"libsyn-post-episode-advanced-destination-' . $destination->destination_id . '-release-time\" />
						</div>
					</div>	
				</div>';
			$destination->expiration_date = '
				<div class=\"form-field radio-options\">
					<label>
						<input type=\"radio\" name=\"set_expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '\" id=\"set_expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '-0\" value=\"0\" checked=\"checked\" class=\"libsyn-form-element show-hide-selector\" data-show-on-value=\"2\" data-toggle-id=\"form-field-wrapper_expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '\" autocomplete=\"off\">
							Never expire
						</label>
						<br>
						<label>
							<input type=\"radio\" name=\"set_expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '\" id=\"set_expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '-2\" value=\"2\" class=\"libsyn-form-element show-hide-selector\" data-show-on-value=\"2\" data-toggle-id=\"form-field-wrapper_expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '\" autocomplete=\"off\">Set new expiration date
					</label>
				</div>
				<div class=\"form-field-wrapper\" id=\"form-field-wrapper_expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '\" style=\"display: none;\">
					<div class=\"form-field date-time\">
						<input type=\"hidden\" name=\"expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '\" value=\"\" data-match-source=\"release_dates\" id=\"expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '\" />
						<div class=\"form-field-wrapper\" id=\"expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '_date-wrapper\">
							<div class=\"form-field\">
								<input type=\"text\" name=\"expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '_date\" id=\"expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '_date\" value=\"\" style=\"width:auto\" size=\"10\" />
							</div>
						</div>
						<div class=\"libsyn-advanced-release-time\">
							<select name=\"expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '_time_select\" id=\"expiration_scheduler_advanced_release_lc__' . $destination->destination_id . '_time_select_select-element\"></select>
							<input type=\"hidden\" value=\"' . get_post_meta( $object->ID, 'libsyn-post-episode-advanced-destination-' . $destination->destination_id . '-expiration-time', true ) . '\" name=\"libsyn-post-episode-advanced-destination-' . $destination->destination_id . '-expiration-time\" id=\"libsyn-post-episode-advanced-destination-' . $destination->destination_id . '-expiration-time\" />
						</div>
					</div>	
				</div>';
		}
		return $destinations;
	}
	
}

?>