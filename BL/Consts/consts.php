<?php
class project
{
	const name = "EF Digital";
	const mainPageName = "EF Digital";
	const uploadPath = "uploads/";

}

class db
{
	//const Server = "34.255.153.122";
	//const Database ="newTest";
	//const Username="oboydak";
	//const Password="Eglence12345";
	const Server = "52.48.17.189";
	const Database ="db_next";
	const Username="hakouser";
	const Password="hk!234?_Du";
	//const Username="efuser";
	//const Password="ef!2_34qasdAp";
	const PortNumber=3306;
	const Socket ="";


}

class mailServer
{
    const domain="eglencefabrikasi.com";
    const host="outlook.office365.com";
    const port="587";
    const username="info@eglencefabrikasi.com";
    const password="Jayu0796";
    const from ="info@eglencefabrikasi.com";
    const fromName = "Eglence Fabrikasi | Entertainment Factory";
}

class dm {
	const key="89ec0e56d4ec142c2319";
	const secret="e62ba83a157639c10f2f724762024c868fb59707";
	const username="eglencefabrikasi";
	const password ="Eglence12345";
	const url = "https://api.dailymotion.com";
}
class twitterKeys {
	const consumer_key ="mQAMDitE2plVkbXgpWns6w";
	const consumer_secret ="QgA8ZXuZnwZ7kV8D4i9sPBVvr9RtGjthfVa15gErhw";
}
class facebookKeys {
	const app_id = "1779211275638343";
	const app_secret = "0294ffc1aa07780dd09a09a2f1eb7157";
	const redirectUrl = 'http://localhost/ef/ff/process/loginFacebook.php';
}
class lastFmKeys {
	const key="0bd997e15bc1e4ac24ab8d5e88586a4f";
}

class linkFire {
	const name = "Bora Turan";
	const OrganisationID="b0c86d10-4530-48a3-be9f-2af4ab960582";
	const BoardName="Bora Turan";
	const BoardID="6bcf4949-f567-406d-b9a9-773ded0e188f";
	//const BoardDomainName="BoraTuran.lnk.to/";
	//const BoardDomainID="5CB3DA88-4D3C-483A-AF54-6CCF28A9F137";
	const BoardDomainName="eglencefabrikasi.lnk.to/";
	const BoardDomainID="611F4E4B-625B-4920-A8C4-4AA0D6E05BF4";
	const MCBoardDomainName="corecollectif.lnk.to/";
	const MCBoardDomainID="db76d3a7-3e86-4088-9463-b2a07a922c62";
	const Username="onderboydak@eglencefabrikasi.com";
	const firstLastName="Onder/Boydak";
	const Password="A8vtVhB;Mxrn#27jg>+NY%";
	const UserID="a09e6908-aeab-4c91-8252-fac4ebfb5d46";
	const ClientName="BoraTuran";
	const ClientID="BoraTuran_API";
	const ClientSecret="UqhftziuME2earVL6t78oQ";
	const WhitelistedIPRange="213.74.31.175";
	const authUrl = "https://auth.linkfire.com/identity/connect/token";
}

