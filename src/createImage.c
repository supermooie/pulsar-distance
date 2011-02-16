// Compile using gcc -o createImage createImage.c libcpgplot.a libpgplot.a -lm -lpng -lX11 -lg2c -lz

#include <stdio.h>
#include <cpgplot.h>
#include <string.h>
#include <stdlib.h>

void sortIt(float *x,float y[8][5000],int n);

int main(int argc, char* argv[])
{
  float x[5000],y[8][5000];
  float maxy=0,start,end;
  float maxx=0;
  char line[1000];
  char head[9][1000];
  char out[1000];
  char psr[100]="";
  char header[100];
  float zooml=-1,zoomr=-1;
  int setzooml=0;
  int setzoomr=0;
  int rot=0;
  int n=0,i,j;
  int sel=-1;
  float xpos,ypos;
  FILE *fin;
  char fname[1000];
  float fx[2],fy[2];

  for (i=0;i<argc;i++)
    {
      if (strcmp(argv[i],"-f")==0)
	strcpy(fname,argv[++i]);
      else if (strcmp(argv[i],"-sel")==0) // Select a frequency channel
	sscanf(argv[++i],"%d",&sel);
      else if (strcmp(argv[i],"-p")==0)
	strcpy(psr,argv[++i]);
      else if (strcmp(argv[i],"-zooml")==0) // Left zoom
	{
	  if (sscanf(argv[++i],"%f",&zooml)!=1)
	    {
	      printf("Unable to parse left zoom command. Please type in a number\n");
	      exit(1);
	    }
	  setzooml=1;
	}
      else if (strcmp(argv[i],"-zoomr")==0) // Right zoom
	{
	  setzoomr=1;
	  if (sscanf(argv[++i],"%f",&zoomr)!=1)
	    {
	      printf("Unable to parse right zoom command. Please type in a number\n");
	      exit(1);
	    }
	}
      else if (strcmp(argv[i],"-rot")==0) // Rotate (integer)
	sscanf(argv[++i],"%d",&rot);
      else if (strcmp(argv[i],"-out")==0)
	strcpy(out,argv[++i]);
    }

  fin = fopen(fname,"r");
  // Read header
  fgets(line,1000,fin);
  strcpy(head[0], strtok(line,","));
  strcpy(head[1], strtok(NULL,","));
  strcpy(head[2], strtok(NULL,","));
  strcpy(head[3], strtok(NULL,","));
  strcpy(head[4], strtok(NULL,","));
  strcpy(head[5], strtok(NULL,","));
  strcpy(head[6], strtok(NULL,","));
  strcpy(head[7], strtok(NULL,","));
  strcpy(head[8], strtok(NULL,","));
  head[8][strlen(head[8])-1]='\0';
  // Now read data
  while (!feof(fin))
    {
      if (fscanf(fin,"%f,%f,%f,%f,%f,%f,%f,%f,%f",&x[n],&y[0][n],&y[1][n],&y[2][n],&y[3][n],
		 &y[4][n],&y[5][n],&y[6][n],&y[7][n])==9)
	{
	  n++;
	}
      
    }
  fclose(fin);

  // Check inputs
  if ((zooml < 0 && setzooml == 1) || (zoomr < 0 && setzoomr==1))
    {
      printf("Zoom range must be greater than 0 seconds\n");
      exit(1);
    }
  else if ((zooml > x[n-1] && setzooml == 1) || (zoomr > x[n-1] && setzoomr == 1))
    {
      printf("Zoom range must be less than the pulsar period of %.2g seconds\n",x[n-1]);
      exit(1);
    }



  if (rot!=0)
    {
      float oldx[5000];
      int pos;
      for (i=0;i<n;i++)
	oldx[i] = x[i];
      for (i=0;i<n;i++)
	{
	  pos = (i+rot);
	  do {
	  if (pos > n-1) pos -= n;
	  if (pos < 0) pos += n;
	  } while (pos > n-1 || pos < 0);
	  x[i] = oldx[pos];
	}
      sortIt(x,y,n);
    }

  for (i=0;i<8;i++)
    {

      for (j=0;j<n;j++)
	{
	  if (x[j] > maxx)
	    maxx = x[j];

	  if (sel==-1)
	    {
	      if (y[i][j] > maxy)
		maxy = y[i][j];
	    }
	  else if (i+1 == sel && y[i][j] > maxy)
	    maxy = y[i][j];
	}
    }
  cpgbeg(0,out,1,1);
  cpgscr(0,1,1,1);
  cpgscr(1,0,0,0);
  cpgsfs(2);
  //cpgsch(1.4);
  //cpgslw(1);

  if (zooml == -1) start = 0;
  else start = zooml;

  if (zoomr == -1) end = maxx;
  else end = zoomr;



  cpgenv(start,end,0,maxy+maxy*0.3,0,1);
  strcpy(header,"PSR ");
  strcat(header,psr);
  cpglab("Time (s)","Amplitude (arbitrary units)",header);
  for (i=0;i<8;i++)
    { 
      if (i+1 == 7)
	cpgsci(12);
     else if (i+1 == 5)
	cpgsci(14);
     else
	cpgsci(i+1);
      if (sel == -1 || (i==sel-1))
	cpgline(n,x,y[i]);
    }

  // Draw legend
  cpgsfs(2);
  cpgsci(1);
  cpgrect(start,end,maxy+maxy*0.1,
	  maxy+maxy*0.3);
  //  cpgsci(1);
  //  cpgsfs(2);
  //  cpgrect(start+(end-start)*0.04,start+(end-start)*0.32,maxy+maxy*0.2-(maxy*0.01),
  //	  maxy+maxy*0.2-9*maxy*0.05);
	  
  xpos = start + (end-start)*0.05;
  ypos = maxy+maxy*0.24;
  for (i=0;i<8;i++)
    {
      fx[0] = xpos;
      fx[1] = xpos + (end-start)*0.05;
      fy[0] = fy[1] = ypos;
      ypos -= (maxy*0.07);
      if ((i+1)%2==0)
	{
	  ypos = maxy+maxy*0.24;
	  xpos += (end-start)*0.24;
	}
      if (i+1 == 7)
	cpgsci(12);
      else if (i+1 == 5)
	cpgsci(14);
      else
	cpgsci(i+1);
      cpgline(2,fx,fy);
      cpgtext(fx[1],fy[0]-(maxy*0.05/2.2),head[i+1]);
    }

  cpgend();
}

// This is a terrible sort!
void sortIt(float *x,float y[8][5000],int n)
{
  int i,j;
  int changed=1;
  float t;
  do {
    changed=0;
    for (i=0;i<n-1;i++)
      {
	if (x[i] > x[i+1])
	  {
	    changed=1;
	    t = x[i];
 	    x[i] = x[i+1];
	    x[i+1] = t;
	    for (j=0;j<8;j++)
	      {
		t = y[j][i];
		y[j][i] = y[j][i+1];
		y[j][i+1] = t;
	      }
	  }
      }

  } while (changed==1);
}
