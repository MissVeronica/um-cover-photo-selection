# UM Cover Photo Selection
Extension to Ultimate Member for Cover Photo Selection from predefined photos.

## Installation
1. Download the zip file and install as a WP Plugin.
2. Activate the plugin.

## UM Profile Form
1. Create a new UM Profile Photo Page for User display and selection of the predefined Cover Photos.
2. Add a Shortcode field to this new Form with the shortcode [cover_photo_selection]
3. Add a Dropdown selection field from the UM Form Builder Predefined fields with the name "My Cover Photo"
4. Selection options for the dropdown field will be created by the shortcode later.
5. Save/Update the new UM Profile Photo Page

## UM Settings
1. UM Settings -> Appearance -> Profile -> "Cover Photo Selection - Photo File extensions"
2. Enter the Cover Photo extensions comma separated. Examples: jpg,jpeg,png
3. UM Settings -> Appearance -> Profile -> "Cover Photo Selection - Photo Page Form ID"
4. Enter the Cover Photos Form ID Number from the UM Forms Page

## Cover Photos
1. The plugin created a folder at the activation  .../wp-content/uploads/ultimatemember/cover-photos/
2. Prepare your predefined Cover Photos to the right size and format for your Forums Cover Photo Field.
3. Upload the Cover Photos to the "cover-photos" folder.
4. Select your Photo filenames depending on the Photo contents.

## Test
1. The Cog Wheel at the Profile Page has an additional Link now: "Cover Photo"
2. Click on this Link and you should get a Profile Edit Page version of your new Photo Page with your uploaded predefined Cover Photos displayed
3. Selection of the User Cover Photo will be made by the dropdown now with all names of the Cover Photos
4. Save the selection and the new Cover Photo will be displayed
5. Administrators can also select Cover Photo for all Users via the User Profile Cog Wheel 
6. Pressing X in the dropdown right corner will clear Cover Photo and the old User selection of private Photo will be used.