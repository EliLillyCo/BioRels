

function getToken() {
    let isTokenExists = Object.keys(localStorage).filter(x => localStorage.getItem(x).includes('tokenType'));
    if (isTokenExists.length > 0) {
        let token = JSON.parse(localStorage[isTokenExists[0]]).secret;
        return token;
    } else {
        return '';
    }
}


async function getUser(emails,render_id)
{
    const [usersData] = await Promise.all([
        getDetailedUserJSON(emails)
    ]);

    if (usersData) {
        console.log(usersData);
        await usersData.forEach(async (user, index) => {
            let user_str = '<div class="user-section w3-col s4-m m4-m l3-m" id="' + index + '" style="order: ' + index + '">';
            if (user.profile_image) {
                const src = "data:image/jpeg;base64, " + user.profile_image;
                user_str += '<div class="user-avatar"><img src="' + src + '"/></div>';
            } else {
                user_str += '<div class="no-user-avatar"></div>';
            }
            user_str += '<div class="user-data">';
            user_str += '<h5>' + ucfirst(user.displayName) + '</h5>';
            //if (users.length === 6) {
            //     const user_match = team_users.filter(x => x.mail === user.mail);
            //    if ('role' in  user_match[0]) user_str += '<p>' + user_match[0].role + '</p>';
            // } else {
            //     user_str += '<p>' + user.jobTitle + '</p>';
            // }
            user_str += '</div></div>';
            $('#' + render_id).append(user_str);
            align_items(render_id);
        });
    }
}

function meetBIORELSTeam()
{
     team_users = [
        {
            mail: '',
            role: ''
        }
    ];

    getUserData(team_users,'team');
}


async function getUserData(user_list, render_id) {
   
    

    let users = user_list.map((x) => x.mail);

    const [usersData] = await Promise.all([
        getDetailedUserJSON(users)
    ]);

    if (render_id === 'team') {
        $('#' + render_id).html('<h3 style="font-weight: bold">Meet the BioRels Team:</h3>');
    }
    if (usersData) {
        await usersData.forEach(async (user, index) => {
            let user_str = '<div class="user-section w3-col s4-m m4-m l3-m" id="' + index + '" style="order: ' + index + '">';
            if (user.profile_image) {
                const src = "data:image/jpeg;base64, " + user.profile_image;
                user_str += '<div class="user-avatar"><img src="' + src + '"/></div>';
            } else {
                user_str += '<div class="no-user-avatar"></div>';
            }
            user_str += '<div class="user-data">';
            user_str += '<span>' + ucfirst(user.displayName) + '</span>';
            //if (users.length === 6) {
                const user_match = team_users.filter(x => x.mail === user.mail);
               if ('role' in  user_match[0]) user_str += '<p>' + user_match[0].role + '</p>';
            // } else {
            //     user_str += '<p>' + user.jobTitle + '</p>';
            // }
            user_str += '</div></div>';
            
            $('#' + render_id).append(user_str);
            align_items(render_id);
        });
    }
}
function ucfirst(str) {

    //Get the first character of the string.
    var firstChar = str.charAt(0);

    //Convert the first character to uppercase.
    firstChar = firstChar.toUpperCase();

    //Remove the original uncapitalized first character.
    var strWithoutFirstChar = str.slice(1);

    //Add the capitalized character to the start of the string.
    var newString = firstChar + strWithoutFirstChar;

    //Return it
    return newString;

}
async function getDetailedUserJSON(users) {
    let token = getToken();

    if (token) {
        const userData = users.map(async (user) => {
            const [avatar_response, response] = await Promise.all([
                await getUserImage(user, token),
                await getUserInfo(user, token)
            ]);
            const avatar = btoa(String.fromCharCode.apply(null, new Uint8Array(avatar_response.data)));
            return {
                profile_image: avatar,
                mail: response.data.mail,
                displayName: response.data.displayName,
                givenName: response.data.givenName,
                surname: response.data.surname,
                jobTitle: response.data.jobTitle,
            }
        });
        const usersData = await Promise.all(userData);
        return usersData;
    }

}

async function getUserInfo(user, token) {
    return new Promise((resolve, reject) => {
        axios(ms_graph_url + `users/` + user, {
            headers: { Authorization: 'Bearer ' + token }
        }).then((response) => {
            resolve(response);
        });
    });
}

async function sendChannelMessage(team_id, channel_id,text) {
    let token = getToken();

    if (token) {
    return new Promise((resolve, reject) => {
        axios.post(ms_graph_url + '/teams/'+team_id+'/channels/'+channel_id+'/messages', {
            body: {
                content:text
              },
            headers: { Authorization: 'Bearer ' + token },
            responseType: "arraybuffer"
        }).then((response) => {
            resolve(response);
        });
    });
}
}


async function getUserImage(user, token) {
    return new Promise((resolve, reject) => {
        axios(ms_graph_url + 'users/' + user + '/photo/$value', {
            headers: { Authorization: 'Bearer ' + token },
            responseType: "arraybuffer"
        }).then((response) => {
            resolve(response);
        });
    });
}

function align_items(render_id) {
    var main = document.getElementById(render_id);

    [].map.call(main.children, Object).sort(function (a, b) {
        return +a.id.match(/\d+/) - +b.id.match(/\d+/);
    }).forEach(function (elem) {
        main.appendChild(elem);
    });
}