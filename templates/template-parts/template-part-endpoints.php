<?php

$base_url = site_url() . '/wp-json/api/v1';

?>

<h4 class="common-title">Endpoints</h4>

<div class="endpoints-wrapper">
    <table class="endpoints-table">
        <thead>
            <tr>
                <th>Endpoint</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= $base_url ?>/get-product-ids?cid=36</td>
                <td>Get product ids</td>
                <td><button class="copy-button">Copy</button></td>
            </tr>
            <tr>
                <td><?= $base_url ?>/set-dropdowns?cid=36</td>
                <td>Set dropdowns</td>
                <td><button class="copy-button">Copy</button></td>
            </tr>
        </tbody>
    </table>
</div>