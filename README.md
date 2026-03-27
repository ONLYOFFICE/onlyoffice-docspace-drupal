# ONLYOFFICE DocSpace module for Drupal

This module enables users to access [ONLYOFFICE DocSpace](https://www.onlyoffice.com/docspace), a room-based collaborative environment, from [Drupal](https://www.drupal.org/), as well as add DocSpace rooms and files to the Drupal pages. 

<p align="center">
  <a href="https://www.onlyoffice.com/office-for-drupal">
    <img width="800" src="https://static-site.onlyoffice.com/public/images/templates/office-for-drupal/website/screen1@2x.png" alt="ONLYOFFICE DocSpace for Drupal">
  </a>
</p>

## Plugin installation and configuration ⚙️ 

Switch to the Extend section in the admin settings and click
**Add new module**. Upload the ONLYOFFICE module and click Continue. When the
uploaded module appears in the list, select it by checking and click the
**Install** button. 

Once ready, go to the module settings via
**Configuration –> MEDIA –> ONLYOFFICE DocSpace Connector settings** and specify
the following parameters:

- DocSpace Service Address
- DocSpace Admin Login and Password

When you click on the Save button, a user with the **Room admin** role will be
created in ONLYOFFICE DocSpace, with the same data as the current Drupal user.

## Exporting users to DocSpace 👥

You need to export users from your Drupal to ONLYOFFICE DocSpace. Click the
**Export Now** button on the module settings page. A page with the list which
contains Drupal users will open. 

To add a user or several users to DocSpace, check them in the list, select
**Invite to DocSpace** from the drop-down list and click the
**Apply to selected items** button.

In the **DocSpace User Status** column of this list, you can track whether
a Drupal user has been added to DocSpace or not:

| Status icon        | Meaning                                                                                |
| ------------------ | -------------------------------------------------------------------------------------- |
| ✅ Green checkmark | A Drupal user with the specified email has been added to DocSpace. Synchronization was successful.  |
| ⬜ Empty value     | There is no Drupal user with the specified email in DocSpace. You can invite them.                  |
| ⏳ Hourglass       | There is a user in DocSpace with the specified email, but there was a synchronization issue. When logging into the DocSpace plugin for the  first time, the user will need to provide a DocSpace login and password to complete synchronization. |

## Working in ONLYOFFICE DocSpace within Drupal

After setting up the module, DocSpace will become available for users with the
**Administer ONLYOFFICE DocSpace connector**
permission: *People –> Permissions -> ONLYOFFICE DocSpace Connector*.

These authorized users can:

- Create Collaboration and Custom rooms.
- Invite and manage room members.
- Collaborate on documents directly in DocSpace.

## Adding DocSpace rooms and files to the Drupal pages

Before you can add a DocSpace room or file to the Drupal page, you need to make
changes to the table structure. Go to *Structure -> Content types* and click
the **Manage fields** button next to the needed element. On the opened page,
click the **Create a new field** button. In the drop-down list, select
ONLYOFFICE DocSpace and specify the name.

If you would like to change the size of the DocSpace element on the published
page, go to *Structure -> Content types* for the desired element and click the
**Manage display** button. In the previously created DocSpace field, click the
gear icon and specify the desired sizes. Once ready, hit Save.

When you are done with the preparatory steps, go to the Content tab, click Add
Content and select the content type to which you have added the
ONLYOFFICE DocSpace element.

To add a room, click the **Select room** button, select the available room from
the list and press Select. 

To add a file, click the **Select file** button, select the desired file from
the room and press Save.

When publishing content, all rooms and files are shared with a public user
(Drupal Viewer), if an anonymous user can view the content
(**View published content** setting).

Access rights to the DocSpace rooms and files on the published Drupal pages
are determined depending on the availability of the DocSpace account:

| User type                           | What they can access                                                                                    |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------- |
| **User with a DocSpace account**    | Can view or edit rooms based on their assigned DocSpace permissions (e.g., Room Admin → full edit access). |
| **User without a DocSpace account** | Can view only Public Rooms embedded in pages.                                                        |

> **Note:**
> - DocSpace left menu is not avaiable;
> - Navigation is possible within the added room only;
> - If users have the Room admin role, they can create new files.

## Need help? User Feedback and Support 💡

* **🐞 Found a bug?** Please report it by creating an [issue](https://github.com/ONLYOFFICE/onlyoffice-docspace-drupal/issues).
* **❓ Have a question?** Ask our community and developers on the [ONLYOFFICE Forum](https://community.onlyoffice.com).
* **👨‍💻 Need help for developers?** Check our [API documentation](https://api.onlyoffice.com).
* **💡 Want to suggest a feature?** Share your ideas on our [feedback platform](https://feedback.onlyoffice.com/forums/966080-your-voice-matters).