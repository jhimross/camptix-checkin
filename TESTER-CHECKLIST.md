# CampTix Check-In — Tester Checklist

Hi! Thanks for helping test the check-in system before the event. Please go through each step below on your **laptop, phone or tablet** (the same device you'd use at the check-in desk), and note anything that doesn't work as expected at the bottom.

Takes about 15–20 minutes. No technical knowledge needed — just click through and check things off.

---

## 1. Login & Dashboard
- [ ] Log in to the site with the admin details provided
- [ ] Click **Check-In** in the left sidebar menu
- [ ] Dashboard loads and shows attendee numbers (Total, Checked In, Not Yet Arrived)

## 2. Look Through Attendees
- [ ] Click **Attendees**
- [ ] Try searching for a name in the search box — results should filter as you type
- [ ] Try the ticket type dropdown filter — list should narrow down
- [ ] Pick any attendee and click the **✎ Edit** button — form should open with their info
- [ ] Click **🖨 Badge** on any attendee — a printable badge should open (no menus/sidebar, just the badge)

## 3. Add a Test Attendee
- [ ] Click **+ Add Attendee**
- [ ] Fill in a fake name and email (e.g. "Test Person" / test@example.com). You can use tool like https://temp-mail.org/en/
- [ ] Save it — you should be taken to their profile page
- [ ] Go back to Attendees list and confirm "Test Person" shows up

## 4. Send a Test QR Email
- [ ] Go to **Send QR Codes**
- [ ] Find "Test Person" in the list and click **Resend**
- [ ] Check the test email inbox — confirm the QR code image actually shows up (not a broken image icon)

## 5. Scan the QR Code — THE MOST IMPORTANT TEST
- [ ] Go to **QR Scanner**
- [ ] Click **Start Scanner** and allow camera access when prompted
- [ ] Point the camera at the QR code from the test email (show it on another phone/screen)
- [ ] Confirm you see a green ✅ success message with the attendee's name
- [ ] Scan the same QR code again — it should now show a yellow ⚠️ "already checked in" message (not an error)
- [ ] Go back to **Attendees** and confirm "Test Person" now shows as checked in

## 6. Print a Badge
- [ ] Open any attendee's badge (Attendees list → 🖨 Badge button)
- [ ] Click **Print Badge**
- [ ] Confirm the print preview shows ONLY the badge — no website menus, no browser clutter

## 7. Clean Up
- [ ] Go back to Attendees, find "Test Person", click **🗑 Del**, confirm the delete
- [ ] Confirm "Test Person" is gone from the list

---

## ✅ / ❌ Quick Summary

| Test | Works? |
|---|---|
| Dashboard loads | ☐ Yes ☐ No |
| Search / filter attendees | ☐ Yes ☐ No |
| Edit an attendee | ☐ Yes ☐ No |
| Print a badge | ☐ Yes ☐ No |
| Add a new attendee | ☐ Yes ☐ No |
| QR email shows the image | ☐ Yes ☐ No |
| Camera scanner opens (back camera) | ☐ Yes ☐ No |
| Scanning a QR checks someone in | ☐ Yes ☐ No |
| Re-scanning shows "already checked in" | ☐ Yes ☐ No |
| Delete an attendee | ☐ Yes ☐ No |

**Device(s) you tested on:** ___________________________

**Anything that didn't work or looked off?**

```


```

Thanks for testing! 🙏
