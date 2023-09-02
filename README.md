## StreamingInstruction example
```
[
    '#EXTM3U',
    '#EXT-X-VERSION:3',
    '#EXT-X-MEDIA-SEQUENCE:1102063',
    '#EXT-X-TARGETDURATION:3',
    '#EXTINF:3.000,',
    '1691696149512.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682',
    '#EXTINF:3.000,',
    '1691696152619.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682',
    '#EXTINF:3.000,',
    '1691696155721.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682',
    '#EXTINF:3.000,'
    '1691696158825.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696161416.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696164526.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696167634.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696170735.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696173837.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696176428.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696179540.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696182643.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696185748.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696188851.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696191446.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696194550.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696197658.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696200759.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696203351.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '#EXTINF:3.000,'
    '1691696206456.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682'
    '',
]
```
## FFMPEG Commands
### get video duration  
1. By ffprobe

`exec("ffprobe -i '{video_absolute_path}' -show_entries format=duration -v quiet -of csv='p=0'");`

2. By ffmpeg

`exec("ffmpeg -i '{video_absolute_path}' 2>&1 | grep Duration | awk '{print $2}' | tr -d");`

### Cut video

`exec("ffmpeg -i '{video_absolute_path}' -ss 00:00:03.040 -t 00:00:05.250 -c copy '{out_put_path}'");`

`-ss` Start at second <br>
`-t` Duration <br>
`-c copy`: Keep same quality

### Concat videos

`exec("ffmpeg -f concat -i '{video_listing_file_path}' -codec copy '{out_put_path}'");`

`-codec copy`: this will keep the same quality with the original video

video listing file
```
file 'video-2.ts'
file 'video-4.ts'
```

### Extract all frames from a video
extract all frame at 10 photos / second. 1/3 for every 3 seconds. 1/10 for every 10 seconds.

`exec("ffmpeg -i '{video_absolute_path}' -r 10 '{export_directory_absolute_path}/%1d.jpg'");`

**NOTE:** Remove -r param will produce real frame rate which might cause calculation issue because video could have different fps rate in different time <br/>

### Extract a frame at specific time
Extract a frame at 00:07:31

`exec("ffmpeg -i '{video_absolute_path}' -ss 00:07:31 -vframes 1 '{export_directory_absolute_path}/ads.jpg'");`

### Cut multiple segments in a video
```
ffmpeg -i '{video_absolute_path}' -filter_complex '
[0:v]trim=start=0:end=442,setpts=PTS-STARTPTS,format=yuv420p[0v];
[0:a]atrim=start=0:end=442,asetpts=PTS-STARTPTS[0a];
[0:v]trim=start=576.4:end=1383.1,setpts=PTS-STARTPTS,format=yuv420p[1v];
[0:a]atrim=start=576.4:end=1383.1,asetpts=PTS-STARTPTS[1a];
[0:v]trim=start=1433.3,setpts=PTS-STARTPTS,format=yuv420p[2v];
[0:a]atrim=start=1433.3,asetpts=PTS-STARTPTS[2a]; 
[0v][0a][1v][1a][2v][2a]concat=n=3:v=1:a=1[outv][outa]' -map '[outv]' -map '[outa]' '{export_directory_absolute_path}/out.mp4'
```
**NOTE:** the output file here is .mp4 otherwise it will be low res

test runcloud deployment