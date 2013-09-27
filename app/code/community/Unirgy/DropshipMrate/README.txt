==== ABSTRACT ====

This add-on integrates Unirgy_Dropship and Auctionmaid_Matrixrate extensions
to provide multiple rates specific for each vendor.

==== INSTRUCTIONS ====

1. Import rates from Matrixrate CSV file.

1.1. delivery_type column (7th or 9th) is required for correct function,
and should be consistent throughout the records, for example:
Ground, 2nd Day, Overnight

1.2. Add vendor name or ID as the last column (8th or 10th, depending on zipcode handling)
Vendor ID is recommended, to avoid spelling mistakes.

2. Go to Sales > Drop Shipping > Shipping Methods > Add/Edit, you should see the
delivery_type values in Matrixrate dropdowns. Choose the correct ones and save

3. Go to Sales > Drop Shipping > Vendors > Add/Edit, and assign correct shipping methods.