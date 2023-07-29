/**
 * This script is used to clean up the 'build-admin' directory after running 'npm run build-admin'.
 * It removes all files and directories except for 'css/admin.css' and 'css/admin.css.map'.
 */

import { readdir, unlink, rmdir, stat } from 'fs/promises';
import { join } from 'path';

const dirPath = './build-admin/';

async function deleteDirectory(dir) {
  const files = await readdir(dir);

  for (const file of files) {
    const filePath = join(dir, file);
    const fileStat = await stat(filePath);

    if (fileStat.isDirectory()) {
      await deleteDirectory(filePath);
      const remainingFilesInDir = await readdir(filePath);
      if (remainingFilesInDir.length === 0 ||
          (remainingFilesInDir.length === 1 && remainingFilesInDir[0] === 'admin.css' && filePath === join(dirPath, 'css'))) {
        // Don't remove 'css' directory in 'build-admin'
        if (filePath !== join(dirPath, 'css')) {
          await rmdir(filePath);
        }
      }
    } else if (filePath !== join(dirPath, 'css/admin.css')) {
      await unlink(filePath);
    }
  }
}

deleteDirectory(dirPath).catch(console.error);
