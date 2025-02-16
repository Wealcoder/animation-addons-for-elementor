export const storeWisList = (key, num) => {
  if (typeof num !== "number") {
    console.error("Only numbers are allowed!");
    return;
  }

  // Get existing array from localStorage or default to an empty array
  let storedData = JSON.parse(localStorage.getItem(key)) || [];

  if (storedData.includes(num)) {
    // If number exists, remove it
    storedData = storedData.filter((n) => n !== num);
    console.log(`Removed ${num} from storage.`);
  } else {
    // Otherwise, add the number
    storedData.push(num);
    console.log(`Added ${num} to storage.`);
  }

  // Update localStorage
  localStorage.setItem(key, JSON.stringify(storedData));
};

export const getWishlist = (key) => {
  return JSON.parse(localStorage.getItem(key)) || [];
};
