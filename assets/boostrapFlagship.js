// Import the Flagship module from the Flagship JavaScript SDK
import {Flagship} from  '@flagship.io/js-sdk'

// Define a function to get the value of a specific cookie
const getCookieValue = (cname) => {
    let name = cname + "=";
    // Decode the cookie string to handle cookies with special characters, e.g. '$'
    let decodedCookie = decodeURIComponent(document.cookie);
    // Split document.cookie on semicolons into an array
    let ca = decodedCookie.split(';');
    // Loop over the array of cookies
    for(let i = 0; i <ca.length; i++) {
        let c = ca[i];
        // If the cookie starts with a space, remove the space
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        // If the cookie name is found at the start of the cookie (c), return the cookie value
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    // If the cookie is not found, return an empty string
    return "";
}

// Add an event listener to the window load event
window.addEventListener('load', ()=>{
    // Start the Flagship SDK with the specified environment ID and API key
    Flagship.start("envId", "apiKey", {
        fetchNow: false // set FetchNow to false
    })

    // Get the visitor ID from the cookies
    const visitorId = getCookieValue("fsVisitorId")

    // Create a new visitor instance with the visitor ID and initial flags data
    const visitor = Flagship.newVisitor({
        visitorId,
        initialFlagsData: initialFlagsData // Set the initial flags data
    });

    // Get the flag value for "my_flag", with a default value specified
    const flag = visitor.getFlag("my_flag", "defaultValue")

    // Get the HTML element with the ID "myFlagValue" and set its inner text to the flag value
    const myFlagValue = document.getElementById("myFlagValue")
    myFlagValue.innerText = flag.getValue()

    // Get the HTML element with the ID "myFlagMetaData" and set its inner text to the flag metadata
    const myFlagMetaData = document.getElementById("myFlagMetaData")
    myFlagMetaData.innerText = JSON.stringify(flag.metadata, null, 4)

})
