SELECT 
	m.groupid,
	d.StartDateTime time,
	d.day,
	m.groupname, 
	m.groupname1, 
	m.meeting location, 
	m.street address, 
	m.city, 
	m.state, 
	m.zipcode postal_code, 
	CONCAT_WS("<br>", m.xstreet, m.footnote1, m.footnote2, m.footnote3) notes, 
	m.boro region, 
	m.lastchange updated, 
	CONCAT_WS("<br>", d.Type, d.SpecialInterest) types,
	m.xstreet,
	m.wc,
	m.SP
FROM MeetingDates d
JOIN Meetings m ON m.MeetingID = d.MeetingID
WHERE d.day <> "" AND m.street <> "" AND boro NOT IN ("NJ", "C")
#AND m.zipcode IN ("10002", "10012", "10014")
ORDER BY m.groupname