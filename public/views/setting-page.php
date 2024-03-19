<style>
    .nav-tabs .nav-link {
        font-size: 14px;
        /* Adjust the font size as needed */
    }
</style>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<div class="wrap">
    <h2> Interest Creator Plugin</h2>
    <h1 class="nav nav-tabs">
        <a href="#settings-tab" class="nav-item nav-link text-black active" data-toggle="tab">Settings</a>
        <a href="#lists-tab" class="nav-item nav-link text-black" data-toggle="tab">Interest Lists</a>
        <a href="#help-tab" class="nav-item nav-link text-black" data-toggle="tab">Help</a>
    </h1>

    <div class="tab-content mt-4">
        <div id="settings-tab" class="tab-pane active">
            <p>Configure your API settings here. Please get you api key from interest tracker portal.</p>
            <form method="post" action="options.php">
                <?php
                // Output security fields for the registered setting 'interesttracker_settings_group'
                settings_fields('interesttracker_settings_group');

                // Get saved options
                $api_key = get_option('interesttracker_api_key');
                $api_endpoint = get_option('interesttracker_api_endpoint');
                if (!trim($api_endpoint)) {
                    $api_endpoint = 'https://dev.interesttracker.org';
                }
                ?>

                <div class="form-group row">
                    <label for="interesttracker_api_key" class="col-sm-2 col-form-label">API KEY</label>
                    <div class="col-sm-6">
                        <input type="text" required max="36" id="interesttracker_api_key" name="interesttracker_api_key" value="<?php echo esc_attr($api_key); ?>" class="form-control" />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="interesttracker_api_endpoint" class="col-sm-2 col-form-label">API ENDPOINT</label>
                    <div class="col-sm-6">
                        <input type="url" id="interesttracker_api_endpoint" name="interesttracker_api_endpoint" value="<?php echo esc_attr($api_endpoint); ?>" class="form-control" />
                    </div>
                </div>

                <?php
                // Submit button
                submit_button('Save Settings', 'primary');
                ?>
            </form>
        </div>

        <div id="lists-tab" class="tab-pane">
            <table id="datatable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>SN</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Church</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Luci</td>
                        <td>Peterson</td>
                        <td>123-456-7890</td>
                        <td>LuciPeterson@example.com</td>
                        <td>Grace Community Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Rabi</td>
                        <td>Gorkhali</td>
                        <td>987-654-3210</td>
                        <td>janesmith@example.com</td>
                        <td>City Hope Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Michael</td>
                        <td>Lucison</td>
                        <td>456-789-0123</td>
                        <td>michaelLucison@example.com</td>
                        <td>Life Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Sarah</td>
                        <td>Williams</td>
                        <td>789-012-3456</td>
                        <td>sarahwilliams@example.com</td>
                        <td>Hope Community Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>David</td>
                        <td>Brown</td>
                        <td>234-567-8901</td>
                        <td>davidbrown@example.com</td>
                        <td>Faith Fellowship Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>Emily</td>
                        <td>Miller</td>
                        <td>890-123-4567</td>
                        <td>emilymiller@example.com</td>
                        <td>New Life Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>Christopher</td>
                        <td>Anderson</td>
                        <td>567-890-1234</td>
                        <td>christopheranderson@example.com</td>
                        <td>Redeemer Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>Amanda</td>
                        <td>Wilson</td>
                        <td>012-345-6789</td>
                        <td>amandawilson@example.com</td>
                        <td>Victory Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>James</td>
                        <td>Thompson</td>
                        <td>345-678-9012</td>
                        <td>jamesthompson@example.com</td>
                        <td>Renewal Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td>Olivia</td>
                        <td>Garcia</td>
                        <td>678-901-2345</td>
                        <td>oliviagarcia@example.com</td>
                        <td>Gracepoint Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td>William</td>
                        <td>Martinez</td>
                        <td>901-234-5678</td>
                        <td>williammartinez@example.com</td>
                        <td>City Life Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>12</td>
                        <td>Isabella</td>
                        <td>Robinson</td>
                        <td>345-678-9012</td>
                        <td>isabellarobinson@example.com</td>
                        <td>Hope Springs Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>13</td>
                        <td>Daniel</td>
                        <td>Clark</td>
                        <td>678-901-2345</td>
                        <td>danielclark@example.com</td>
                        <td>New Horizon Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>14</td>
                        <td>Sophia</td>
                        <td>Walker</td>
                        <td>901-234-5678</td>
                        <td>sophiawalker@example.com</td>
                        <td>Hope Alive Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>15</td>
                        <td>Matthew</td>
                        <td>Perez</td>
                        <td>234-567-8901</td>
                        <td>matthewperez@example.com</td>
                        <td>Harvest Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>16</td>
                        <td>Amelia</td>
                        <td>Hall</td>
                        <td>890-123-4567</td>
                        <td>ameliahall@example.com</td>
                        <td>New Creation Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>17</td>
                        <td>Joseph</td>
                        <td>You</td>
                        <td>567-890-1234</td>
                        <td>josephyou@example.com</td>
                        <td>Revival Church</td>
                        <td><button>View</button></td>
                    </tr>
                    <tr>
                        <td>18</td>
                        <td>Mia</td>
                        <td>Young</td>
                        <td>012-345-6789</td>
                        <td>miayoung@example.com</td>
                        <td>Grace Church</td>
                        <td><button>View</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="help-tab" class="tab-pane">
            <p>Welcome to our help desk, where we're dedicated to resolving your technical issues swiftly and effectively. Our team of experienced professionals is standing by, ready to assist you with any software or hardware-related challenges you may encounter. Whether it's troubleshooting, software installations, or system optimizations, we've got you covered. Rest assured, your satisfaction is our priority, and we strive to provide excellent customer service with every interaction. Feel free to reach out to us via phone, email, or our online chat support for prompt assistance. Thank you for choosing our help desk, where problems meet solutions seamlessly.</p>
            <p>Welcome to our help desk, where we're dedicated to resolving your technical issues swiftly and effectively. Our team of experienced professionals is standing by, ready to assist you with any software or hardware-related challenges you may encounter. Whether it's troubleshooting, software installations, or system optimizations, we've got you covered. Rest assured, your satisfaction is our priority, and we strive to provide excellent customer service with every interaction. Feel free to reach out to us via phone, email, or our online chat support for prompt assistance. Thank you for choosing our help desk, where problems meet solutions seamlessly.</p>
            <p>Welcome to our help desk, where we're dedicated to resolving your technical issues swiftly and effectively. Our team of experienced professionals is standing by, ready to assist you with any software or hardware-related challenges you may encounter. Whether it's troubleshooting, software installations, or system optimizations, we've got you covered. Rest assured, your satisfaction is our priority, and we strive to provide excellent customer service with every interaction. Feel free to reach out to us via phone, email, or our online chat support for prompt assistance. Thank you for choosing our help desk, where problems meet solutions seamlessly.</p>
        </div>
    </div>
</div>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script>
    jQuery(document).ready(function($) {
        $('#datatable').DataTable();
        $('.nav-tabs a').on('click', function(e) {
            e.preventDefault();
            $(this).tab('show');
        });
    });
</script>