# interesttracker-wp-integrator
WordPress plugin for integrating Gravity Forms responses with InterestTracker.

## Authentication Implementation
Please follow these steps to get the access_token. Sign in to GitHub.
<p>1. Go to "Click Profile" > "Settings" > "Developer settings" > "Personal access tokens."</p>
<p>2. Click "Generate new token(Tokens classic)."</p>
<p>3. Tick required all token permissions.</p>
<p>4. Click "Generate token" and copy it and save it.</p>
<p>5. Now in your main file.The main file is generally your plugin-name.php. Update following values</p>
<p>6. $myUpdateChecker->setAuthentication('YOUR_GITHUB_PERSNONAL_ACCESS_TOKEN(CLASSIC)');</p>
<p>7. $myUpdateChecker = PucFactory::buildUpdateChecker(
    <!-- 'https://github.com/rabigorkhali/wp-plugin/', -->
    'YOUR_GIT_HUB_REPO_LINK',
    __FILE__,
    <!-- 'interesttracker-wp-integrator-rabi' -->
    'PLUGIN_SLUG_VALUE'
);
</p>
<p>8. Note: It can be done by only repo owner. <p></p>


## How to make release

<p>Step1: Make changes in main branch - either by merge with other branch or making changes in main branch.</p>
Step 2: Increment version in main file(For eg: if there is Version: 0.1 then change to Version: 0.2). The main file is basically in root location with same name of plugin(main file name: interesttracker-wp-integrator.php).</p>
Step 3: Commit and push to main branch.</p>
Step 4: Go to git hub repo. Click "tags". It is just beside "Go to file" search box.</p>
Step 5: Click "Draft a new release".</p>
Step 6: Enter data in "Choose a tag" , "Release Title", "Describe the title" .</p>
Step 7: Go to your wordpress plugin list. Click button "check for updates" to get new update .</p>



Developer: Rabi Gorkhali
Email: rabigorkhaly@gmail.com <br>
Owned By: Sermon View
