<?php
include 'db.php';
include 'amo_api.php';

if (isset($_POST) && !empty($_POST)) {

    if (isset($_POST['contacts']) && !empty($_POST['contacts'])) {
        $data = json_decode($_POST['contacts'], true);
        editNoteContactsAmoCrm($data);
    } else if (isset($_POST['leads']) && !empty($_POST['leads'])) {
        $data = json_decode($_POST['leads'], true);
        editNoteLeadsAmoCrm($data);
    }

}


function editNoteContactsAmoCrm($data) {
    global $connection;
    if (isset($data['add'])) {
        $add_data = $data['add'];

        foreach ($add_data as $add_d) {

           foreach ($add_d['linked_leads_id'] as $lead_id) {
                $contact = $connection->query("SELECT data FROM contacts WHERE entity_id=" . $add_d['id']);

                if ($contact->num_rows == 0) {

                    $date_time = DateTime::createFromFormat('U', $add_d['date_create']);
                    $date_time->setTimezone(new DateTimeZone('Europe/Moscow'));

                    $link = 'https://' . SUBDOMAIN . '/api/v4/users/' . $add_d['responsible_user_id'];

                    $user_info = getDataAmoCrm($link);

                    $link = 'https://' . SUBDOMAIN . '/api/v4/leads/notes';

                    $data = [
                        'params' => [
                            'entity_id' => intval($lead_id['ID']),
                            'text' => 'Добавлен новый контакт: ' . (!empty($add_d['name']) ? $add_d['name'] : '')  . " " . $user_info['name']. " " . date_format($date_time, 'H:i:s'),
                            'note_type' => 'common'
                        ]
                    ];

                    sendDataAmoCrm($link, $data);

                    $link = 'https://' . SUBDOMAIN . '/api/v4/contacts/' . intval($add_d['id']);
                    $contact = getDataAmoCrm($link);

                    $connection->query("INSERT INTO contacts (entity_id, data) VALUES (" . $add_d['id'] . ", '" . json_encode($contact, JSON_UNESCAPED_UNICODE) . "')");
                }
            }
        }
    } else if (isset($data['update'])) {

        if (isset($data['update'])) {
            $update_data = $data['update'];

            foreach ($update_data as $update_d) {

                $contact = $connection->query("SELECT data FROM contacts WHERE entity_id=" . $update_d['id']);

                if ($contact->num_rows > 0) {

                    $link = 'https://' . SUBDOMAIN . '/api/v4/contacts/' . intval($update_d['id']);
                    $new_contact = getDataAmoCrm($link);
                    $old_contact = json_decode($contact->fetch_assoc()['data'], true);

                    $updatedFields = [];

                    if ($new_contact['name'] != $old_contact['name']) {
                        $updatedFields[] = [
                            'name' => 'Название',
                            'values' => [$new_contact['name']]
                        ];
                    }

                    foreach ($new_contact['custom_fields_values'] as $n_key => $new_con) {

                        foreach ($old_contact['custom_fields_values'] as $o_key => $old_con) {

                            if ($new_con['field_id'] == $old_con['field_id']) {

                                if ($new_con['values'] != $old_con['values']) {
                                    $new_values = [];

                                    foreach ($new_con['values'] as $key => $val) {

                                        if ($val['value'] != $old_con['values'][$key]['value']) {
                                            $new_values[] = $val['value'];
                                        }
                                    }

                                    $updatedFields[] = [
                                        'name' => $new_con['field_name'],
                                        'values' => $new_values
                                    ];
                                }

                            }
                        }

                    }

                    if (!empty($updatedFields)) {
                        $connection->query("UPDATE contacts SET data = '" . json_encode($new_contact, JSON_UNESCAPED_UNICODE) . "' WHERE entity_id=" . $update_d['id']);

                        foreach ($update_d['linked_leads_id'] as $lead_id) {
                            $link = 'https://' . SUBDOMAIN . '/api/v4/leads/notes';
                            $text = '';
                            foreach ($updatedFields as $field) {
                                $text .= $field['name'] . ': ' . implode(', ', $field['values']). ' ';
                            }

                            $data = [
                                'params' => [
                                    'entity_id' => intval($lead_id['ID']),
                                    'text' => 'Изменены поля: '. $text,
                                    'note_type' => 'common'
                                ]
                            ];

                            sendDataAmoCrm($link, $data);
                        }
                    }

                }
            }

        }
    }
}

function editNoteLeadsAmoCrm($data) {
    global $connection;

    if (isset($data['add'])) {
        $add_data = $data['add'];

        foreach ($add_data as $add_d) {
            $lead = $connection->query("SELECT data FROM leads WHERE entity_id=" . $add_d['id']);

            if ($lead->num_rows == 0) {
                $date_time = DateTime::createFromFormat('U', $add_d['date_create']);
                $date_time->setTimezone(new DateTimeZone('Europe/Moscow'));

                $link = 'https://' . SUBDOMAIN . '/api/v4/users/' . $add_d['responsible_user_id'];

                $user_info = getDataAmoCrm($link);

                $link = 'https://' . SUBDOMAIN . '/api/v4/leads/notes';

                $data = [
                    'params' => [
                        'entity_id' => intval($add_d['id']),
                        'text' => 'Добавлена новая сделка: '. $add_d['name']  . " " . $user_info['name']. " " . date_format($date_time, 'H:i:s'),
                        'note_type' => 'common'
                    ]
                ];

                sendDataAmoCrm($link, $data);

                $connection->query("INSERT INTO leads (entity_id, data) VALUES (" . $add_d['id'] . ", '" . json_encode($add_d, JSON_UNESCAPED_UNICODE) . "')");
            }
        }
    } else if (isset($data['update'])) {
        if (isset($data['update'])) {
            $update_data = $data['update'];

            foreach ($update_data as $update_d) {
                $lead = $connection->query("SELECT data FROM leads WHERE entity_id=" . $update_d['id']);

                if ($lead->num_rows > 0) {
                    $link = 'https://' . SUBDOMAIN . '/api/v4/leads/' . intval($update_d['id']);
                    $new_lead = getDataAmoCrm($link);
                    $old_lead = json_decode($lead->fetch_assoc()['data'], true);

                    $updatedFields = [];

                    if ($new_lead['name'] != $old_lead['name']) {
                        $updatedFields[] = [
                            'name' => 'Название',
                            'values' => $new_lead['name']
                        ];
                    }

                    if (!empty($updatedFields)) {
                        $link = 'https://' . SUBDOMAIN . '/api/v4/leads/notes';
                        $text = '';

                        foreach ($updatedFields as $field) {
                            $text .= $field['name'] . ': ' . $field['values'] . ' ';
                        }

                        $data = [
                            'params' => [
                                'entity_id' => intval($update_d['id']),
                                'text' => 'Изменены поля: '. $text,
                                'note_type' => 'common'
                            ]
                        ];

                        sendDataAmoCrm($link, $data);
                    }
                }
            }
        }
    }

}
